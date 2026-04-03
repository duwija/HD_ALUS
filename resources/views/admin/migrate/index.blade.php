@extends('admin.layouts.app')

@section('styles')
<style>
    .tenant-result { border-left: 4px solid #dee2e6; }
    .tenant-result.success { border-left-color: #28a745; }
    .tenant-result.error   { border-left-color: #dc3545; }
    .tenant-result.skipped { border-left-color: #ffc107; }
    .output-pre {
        background: #1e1e1e;
        color: #d4d4d4;
        font-size: 0.8rem;
        border-radius: 4px;
        max-height: 300px;
        overflow-y: auto;
        white-space: pre-wrap;
        word-break: break-all;
    }
    #run-btn.loading { pointer-events: none; opacity: 0.7; }
</style>
@endsection

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-database mr-2"></i> Tenant Database Migration</h1>
    <a href="{{ route('admin.documentation.show', 'migration-guide') }}" class="btn btn-sm btn-outline-secondary" target="_blank">
        <i class="fas fa-book mr-1"></i> Dokumentasi
    </a>
</div>

{{-- Alert info --}}
<div class="alert alert-info">
    <i class="fas fa-info-circle mr-1"></i>
    Jalankan <strong>database migration</strong> ke semua tenant yang terdaftar.
    Setara dengan perintah <code>php artisan tenant:migrate-all</code> di terminal.
    Gunakan <strong>Dry Run</strong> terlebih dahulu untuk melihat perubahan tanpa mengeksekusi.
</div>

<div class="row">
    {{-- Form Kontrol --}}
    <div class="col-md-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header"><i class="fas fa-sliders-h mr-1"></i> Opsi Migrasi</div>
            <div class="card-body">
                <form id="migrate-form">
                    @csrf

                    <div class="form-group">
                        <label><i class="fas fa-filter mr-1"></i> Filter Tenant <small class="text-muted">(opsional)</small></label>
                        <select name="tenant" id="tenant-select" class="form-control">
                            <option value="">— Semua Tenant Aktif —</option>
                            @foreach($tenants as $t)
                                <option value="{{ $t->rescode }}">
                                    [{{ $t->rescode }}] {{ $t->domain }} ({{ $t->db_database }})
                                    @if(!$t->is_active) ⛔ @endif
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Kosongkan untuk migrate semua tenant aktif.</small>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="include-inactive" name="include_inactive" value="1">
                            <label class="custom-control-label" for="include-inactive">
                                Includesikan tenant non-aktif
                            </label>
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="pretend" name="pretend" value="1" checked>
                            <label class="custom-control-label" for="pretend">
                                <strong>Dry Run</strong> <span class="badge badge-warning">Direkomendasikan</span>
                                <small class="d-block text-muted">Tampilkan SQL tanpa eksekusi.</small>
                            </label>
                        </div>
                    </div>

                    <hr>

                    <button type="submit" id="run-btn" class="btn btn-primary btn-block">
                        <i class="fas fa-play mr-1"></i> <span id="run-label">Jalankan Migrasi</span>
                    </button>
                </form>
            </div>
        </div>

        {{-- Ringkasan --}}
        <div id="summary-card" class="card shadow-sm d-none">
            <div class="card-header"><i class="fas fa-chart-bar mr-1"></i> Ringkasan</div>
            <div class="card-body p-3">
                <div class="d-flex justify-content-around text-center">
                    <div>
                        <div class="h3 text-success mb-0" id="count-success">0</div>
                        <small class="text-muted">Berhasil</small>
                    </div>
                    <div>
                        <div class="h3 text-danger mb-0" id="count-error">0</div>
                        <small class="text-muted">Gagal</small>
                    </div>
                    <div>
                        <div class="h3 text-warning mb-0" id="count-skipped">0</div>
                        <small class="text-muted">Dilewati</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Hasil --}}
    <div class="col-md-8">
        <div id="results-wrapper">
            <div id="results-placeholder" class="text-center text-muted py-5">
                <i class="fas fa-database fa-3x mb-3 d-block"></i>
                Klik <strong>Jalankan Migrasi</strong> untuk memulai.
            </div>
            <div id="results-container"></div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.getElementById('migrate-form').addEventListener('submit', function(e) {
    e.preventDefault();

    const btn       = document.getElementById('run-btn');
    const label     = document.getElementById('run-label');
    const container = document.getElementById('results-container');
    const placeholder = document.getElementById('results-placeholder');
    const summaryCard = document.getElementById('summary-card');

    const pretend        = document.getElementById('pretend').checked;
    const tenant         = document.getElementById('tenant-select').value;
    const includeInactive = document.getElementById('include-inactive').checked;

    // Loading state
    btn.classList.add('loading');
    label.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Memproses...';
    container.innerHTML = '';
    placeholder.classList.add('d-none');
    summaryCard.classList.add('d-none');

    const formData = new FormData();
    formData.append('_token', document.querySelector('input[name=_token]').value);
    formData.append('pretend', pretend ? '1' : '0');
    formData.append('include_inactive', includeInactive ? '1' : '0');
    if (tenant) formData.append('tenant', tenant);

    fetch('{{ route("admin.migrate.run") }}', {
        method: 'POST',
        body: formData,
    })
    .then(res => res.json())
    .then(data => {
        renderResults(data, pretend);
    })
    .catch(err => {
        container.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                Request gagal: ${err.message}
            </div>`;
    })
    .finally(() => {
        btn.classList.remove('loading');
        label.innerHTML = '<i class="fas fa-play mr-1"></i> Jalankan Migrasi';
    });
});

function renderResults(data, pretend) {
    const container  = document.getElementById('results-container');
    const summaryCard = document.getElementById('summary-card');

    let successCount = 0, errorCount = 0, skippedCount = 0;

    // Global alert
    const alertClass = data.success ? 'alert-success' : 'alert-danger';
    const alertIcon  = data.success ? 'fa-check-circle' : 'fa-times-circle';
    const modeLabel  = pretend ? ' <span class="badge badge-warning">Dry Run</span>' : '';
    let html = `<div class="alert ${alertClass} mb-3">
        <i class="fas ${alertIcon} mr-1"></i> ${data.message}${modeLabel}
    </div>`;

    // Per-tenant cards
    (data.results || []).forEach(r => {
        const statusClass = r.status === 'success' ? 'success'
                          : r.status === 'skipped' ? 'skipped' : 'error';
        const badgeClass  = r.status === 'success' ? 'badge-success'
                          : r.status === 'skipped'  ? 'badge-warning' : 'badge-danger';
        const icon        = r.status === 'success' ? 'fa-check' :
                            r.status === 'skipped'  ? 'fa-minus-circle' : 'fa-times';

        if (r.status === 'success') successCount++;
        else if (r.status === 'skipped') skippedCount++;
        else errorCount++;

        const outputHtml = r.output
            ? `<pre class="output-pre mt-2 mb-0">${escapeHtml(r.output)}</pre>`
            : '';

        html += `
        <div class="card mb-2 tenant-result ${statusClass}">
            <div class="card-body py-2 px-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <strong><i class="fas fa-server mr-1"></i>${escapeHtml(r.tenant)}</strong>
                        <small class="text-muted ml-2">${escapeHtml(r.database)}</small>
                    </div>
                    <span class="badge ${badgeClass}">
                        <i class="fas ${icon} mr-1"></i>${r.status.toUpperCase()}
                    </span>
                </div>
                ${outputHtml}
            </div>
        </div>`;
    });

    container.innerHTML = html;

    // Update summary counters
    document.getElementById('count-success').textContent = successCount;
    document.getElementById('count-error').textContent   = errorCount;
    document.getElementById('count-skipped').textContent = skippedCount;
    summaryCard.classList.remove('d-none');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(text || ''));
    return div.innerHTML;
}
</script>
@endsection
