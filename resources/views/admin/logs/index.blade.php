@extends('admin.layouts.app')

@section('styles')
<style>
.log-type-badge { font-size: 0.7rem; padding: 2px 7px; border-radius: 10px; font-weight: 600; }
.log-invoice    { background: #d4edda; color: #155724; }
.log-notif      { background: #cce5ff; color: #004085; }
.log-payment    { background: #fff3cd; color: #856404; }
.log-olt        { background: #d1ecf1; color: #0c5460; }
.log-jobs       { background: #e2e3e5; color: #383d41; }
.log-auth       { background: #f8d7da; color: #721c24; }
.log-other      { background: #f5f5f5; color: #555; }
.log-card       { border-left: 4px solid; }
.log-card.invoice  { border-color: #28a745; }
.log-card.notif    { border-color: #007bff; }
.log-card.payment  { border-color: #ffc107; }
.log-card.olt      { border-color: #17a2b8; }
.log-card.jobs     { border-color: #6c757d; }
.log-card.auth     { border-color: #dc3545; }
.log-card.other    { border-color: #aaa; }
.file-row:hover { background: #f8f9fa; }
</style>
@endsection

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="h4 mb-1"><i class="fas fa-scroll text-secondary mr-2"></i>Application Logs</h2>
            <small class="text-muted">
                Tenant: <strong>{{ strtoupper($tenantKey) }}</strong> &bull;
                <span class="text-success"><i class="fas fa-lock mr-1"></i>Log PHP channel ditulis per-tenant</span> &bull;
                <span class="text-info"><i class="fas fa-code mr-1"></i>OLT log dari Python script</span>
            </small>
        </div>
        <a href="{{ route('admin.tenants.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Back
        </a>
    </div>

    @php
        $typeLabels = [
            'invoice' => ['label' => 'Invoice',        'icon' => 'fa-file-invoice',   'class' => 'invoice', 'tenant' => true],
            'notif'   => ['label' => 'Notifikasi',     'icon' => 'fa-bell',           'class' => 'notif',   'tenant' => true],
            'payment' => ['label' => 'Payment',        'icon' => 'fa-credit-card',    'class' => 'payment', 'tenant' => true],
            'isolir'  => ['label' => 'Isolir',         'icon' => 'fa-ban',            'class' => 'payment', 'tenant' => true],
            'auth'    => ['label' => 'Auth',           'icon' => 'fa-lock',           'class' => 'auth',    'tenant' => true],
            'jobs'    => ['label' => 'Jobs Process',   'icon' => 'fa-cogs',           'class' => 'jobs',    'tenant' => true],
            'olt'     => ['label' => 'OLT / Device',   'icon' => 'fa-network-wired',  'class' => 'olt',     'tenant' => false],
            'other'   => ['label' => 'Lainnya',        'icon' => 'fa-file-alt',       'class' => 'other',   'tenant' => false],
            'legacy'  => ['label' => 'Log Lama (shared)', 'icon' => 'fa-archive',    'class' => 'other',   'tenant' => false],
        ];
    @endphp

    @if(empty($grouped))
        <div class="alert alert-info"><i class="fas fa-info-circle mr-1"></i> Tidak ada file log ditemukan.</div>
    @endif

    @foreach($typeLabels as $typeKey => $meta)
        @if(!empty($grouped[$typeKey]))
        <div class="card shadow-sm mb-4 log-card {{ $meta['class'] }}">
            <div class="card-header py-2 d-flex align-items-center">
                <i class="fas {{ $meta['icon'] }} mr-2 text-secondary"></i>
                <strong>{{ $meta['label'] }}</strong>
                <span class="badge badge-secondary ml-2">{{ count($grouped[$typeKey]) }} file</span>
                @if($meta['tenant'])
                <span class="badge badge-success ml-1" style="font-size:10px">
                    <i class="fas fa-lock mr-1"></i>{{ strtoupper($tenantKey) }} only
                </span>
                @elseif($typeKey === 'legacy')
                <span class="badge badge-warning ml-1" style="font-size:10px">
                    <i class="fas fa-exclamation-triangle mr-1"></i>shared sebelum migrasi
                </span>
                @endif
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th class="pl-3">Nama File</th>
                            <th>Ukuran</th>
                            <th>Terakhir Diubah</th>
                            <th class="text-right pr-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($grouped[$typeKey] as $f)
                        <tr class="file-row">
                            <td class="pl-3">
                                <i class="fas fa-file-alt text-muted mr-1"></i>
                                <code>{{ $f['label'] }}</code>
                            </td>
                            <td>
                                <span class="text-muted small">
                                    @if($f['size'] > 1048576)
                                        {{ number_format($f['size'] / 1048576, 2) }} MB
                                    @elseif($f['size'] > 1024)
                                        {{ number_format($f['size'] / 1024, 1) }} KB
                                    @else
                                        {{ $f['size'] }} B
                                    @endif
                                </span>
                            </td>
                            <td class="small text-muted">{{ date('d M Y H:i', $f['modified']) }}</td>
                            <td class="text-right pr-3">
                                <a href="{{ route('admin.logs.view', ['file' => $f['name']]) }}"
                                   class="btn btn-xs btn-outline-primary btn-sm py-0 px-2">
                                    <i class="fas fa-eye mr-1"></i> Lihat
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    @endforeach

</div>
@endsection
