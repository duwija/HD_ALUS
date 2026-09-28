@extends('admin.layouts.app')

@section('styles')
<style>
    .tax-panel { background: linear-gradient(180deg,#f8fafc,#fff); }
    .tax-tile { position: relative; overflow: hidden; border-radius: 12px; padding: 16px 18px; color: #fff; height: 100%; box-shadow: 0 4px 14px rgba(0,0,0,.12); transition: transform .15s; }
    .tax-tile:hover { transform: translateY(-3px); }
    .tax-tile .tax-icon { position: absolute; right: 12px; top: 10px; font-size: 3rem; opacity: .18; }
    .tax-label { font-size: .8rem; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; opacity: .95; }
    .tax-rate { background: rgba(255,255,255,.25); border-radius: 10px; padding: 1px 8px; margin-left: 4px; font-size: .75rem; }
    .tax-value { font-size: 1.5rem; font-weight: 700; margin: 6px 0 2px; }
    .tax-sub { font-size: .75rem; opacity: .85; }
    .tile-dpp { background: linear-gradient(135deg,#64748b,#334155); }
    .tile-ppn { background: linear-gradient(135deg,#06b6d4,#0e7490); }
    .tile-bhp { background: linear-gradient(135deg,#8b5cf6,#5b21b6); }
    .tile-uso { background: linear-gradient(135deg,#f59e0b,#b45309); }
    .tax-total { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; background: linear-gradient(135deg,#ef4444,#991b1b); color: #fff; border-radius: 12px; padding: 16px 22px; box-shadow: 0 6px 18px rgba(185,28,28,.35); }
    .tax-total-label { font-size: 1.05rem; font-weight: 700; }
    .tax-total-sub { font-size: .78rem; opacity: .85; }
    .tax-total-value { font-size: 2rem; font-weight: 800; }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">
                        <i class="fas fa-chart-line"></i>
                        @if ($isSupervisor)
                            Dashboard &mdash; Resume Tenant Anda
                        @else
                            Dashboard &mdash; Resume Seluruh Tenant
                        @endif
                    </h3>
                    <div class="d-flex align-items-center">
                        <form method="GET" action="{{ route('admin.dashboard') }}" class="mr-2 mb-0">
                            <select name="month" class="form-control form-control-sm" onchange="this.form.submit()">
                                @foreach ($monthOptions as $m)
                                    <option value="{{ $m->format('Y-m') }}" {{ $m->format('Y-m') === $selectedMonth->format('Y-m') ? 'selected' : '' }}>
                                        {{ $m->translatedFormat('F Y') }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                        <a href="{{ route('admin.tenants.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-building"></i> Tenant Management
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if ($isSupervisor)
                        <div class="alert alert-info small">
                            <i class="fas fa-info-circle"></i> Data di bawah ini hanya mencakup tenant yang di-assign ke akun Anda, dan dibatasi pada merchant yang telah di-set untuk masing-masing tenant.
                        </div>
                    @endif
                    <!-- Summary cards -->
                    <div class="row mb-4">
                        <div class="col-md-2 col-sm-4 col-6 mb-3">
                            <div class="card text-white bg-primary h-100">
                                <div class="card-body text-center">
                                    <div class="h3 mb-0">{{ $totals['tenants'] }}</div>
                                    <small>Total Tenant</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-4 col-6 mb-3">
                            <div class="card text-white bg-success h-100">
                                <div class="card-body text-center">
                                    <div class="h3 mb-0">{{ $totals['tenants_active'] }}</div>
                                    <small>Tenant Aktif</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-4 col-6 mb-3">
                            <div class="card text-white bg-secondary h-100">
                                <div class="card-body text-center">
                                    <div class="h3 mb-0">{{ $totals['tenants_inactive'] }}</div>
                                    <small>Tenant Nonaktif</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-4 col-6 mb-3">
                            <div class="card text-white bg-info h-100">
                                <div class="card-body text-center">
                                    <div class="h3 mb-0">{{ number_format($totals['customers']) }}</div>
                                    <small>Total Customer</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-4 col-6 mb-3">
                            <div class="card text-white bg-warning h-100">
                                <div class="card-body text-center">
                                    <div class="h3 mb-0">{{ number_format($totals['unpaid_invoices']) }}</div>
                                    <small>Invoice Belum Lunas</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-4 col-6 mb-3">
                            <div class="card text-white bg-dark h-100">
                                <div class="card-body text-center">
                                    <div class="h6 mb-0">Rp {{ number_format($totals['revenue_this_month'], 0, ',', '.') }}</div>
                                    <small>Pendapatan {{ $selectedMonth->translatedFormat('F Y') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-secondary small mb-4">
                        <i class="fas fa-users"></i> Customer Aktif (semua tenant): <strong>{{ number_format($totals['customers_active']) }}</strong>
                        &nbsp;|&nbsp;
                        <i class="fas fa-user-clock"></i> Customer Potential (semua tenant): <strong>{{ number_format($totals['customers_potential']) }}</strong>
                    </div>

                    <!-- Tax summary -->
                    <div class="card mb-4">
                        <div class="card-body tax-panel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0"><i class="fas fa-file-invoice-dollar text-danger"></i> Ringkasan Pajak {{ $selectedMonth->translatedFormat('F Y') }} (Semua Tenant)</h5>
                                <small class="text-muted">Pendapatan sudah termasuk PPN &middot; BHP &amp; USO dihitung dari DPP</small>
                            </div>
                            <div class="row">
                                <div class="col-lg-3 col-6 mb-3">
                                    <div class="tax-tile tile-dpp">
                                        <i class="fas fa-coins tax-icon"></i>
                                        <div class="tax-label">DPP</div>
                                        <div class="tax-value">Rp {{ number_format($totals['tax']['dpp'], 0, ',', '.') }}</div>
                                        <div class="tax-sub">Pendapatan &divide; 1,11</div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-6 mb-3">
                                    <div class="tax-tile tile-ppn">
                                        <i class="fas fa-percent tax-icon"></i>
                                        <div class="tax-label">PPN <span class="tax-rate">{{ number_format($totals['tax']['ppn_rate'], 0, ',', '.') }}%</span></div>
                                        <div class="tax-value">Rp {{ number_format($totals['tax']['ppn'], 0, ',', '.') }}</div>
                                        <div class="tax-sub">Pendapatan &minus; DPP</div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-6 mb-3">
                                    <div class="tax-tile tile-bhp">
                                        <i class="fas fa-broadcast-tower tax-icon"></i>
                                        <div class="tax-label">BHP Telekomunikasi <span class="tax-rate">{{ number_format($totals['tax']['bhp_rate'], 1, ',', '.') }}%</span></div>
                                        <div class="tax-value">Rp {{ number_format($totals['tax']['bhp'], 0, ',', '.') }}</div>
                                        <div class="tax-sub">dari DPP</div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-6 mb-3">
                                    <div class="tax-tile tile-uso">
                                        <i class="fas fa-network-wired tax-icon"></i>
                                        <div class="tax-label">USO <span class="tax-rate">{{ number_format($totals['tax']['uso_rate'], 2, ',', '.') }}%</span></div>
                                        <div class="tax-value">Rp {{ number_format($totals['tax']['uso'], 0, ',', '.') }}</div>
                                        <div class="tax-sub">dari DPP</div>
                                    </div>
                                </div>
                            </div>
                            <div class="tax-total">
                                <div>
                                    <div class="tax-total-label"><i class="fas fa-landmark"></i> Total Kewajiban Pajak</div>
                                    <div class="tax-total-sub">PPN + BHP Telekomunikasi + USO &middot; semua tenant</div>
                                </div>
                                <div class="tax-total-value">Rp {{ number_format($totals['tax']['total_kewajiban'], 0, ',', '.') }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Per-tenant breakdown -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover table-sm">
                            <thead class="thead-dark">
                                <tr>
                                    <th width="40">#</th>
                                    <th>Tenant</th>
                                    <th class="text-center">Jenis Tier</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-right">Total Customer</th>
                                    <th class="text-right">Customer Aktif</th>
                                    <th class="text-right">Invoice Belum Lunas</th>
                                    <th class="text-right">Pendapatan {{ $selectedMonth->translatedFormat('M Y') }}</th>
                                    <th class="text-right">Pajak {{ $selectedMonth->translatedFormat('M Y') }}</th>
                                    <th class="text-center" width="140">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rows as $i => $row)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>
                                        <strong>{{ $row['tenant']->app_name }}</strong><br>
                                        <small class="text-muted">{{ $row['tenant']->domain }}</small>
                                    </td>
                                    <td class="text-center">
                                        @php $tierInfo = \App\Tenant::TIERS[$row['tenant']->tier] ?? null; @endphp
                                        @if ($tierInfo)
                                            <span class="badge badge-{{ $tierInfo['badge'] }}">Tier {{ $row['tenant']->tier }} · {{ $tierInfo['name'] }}</span><br>
                                            <small class="text-muted">{{ $tierInfo['split'] }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($row['tenant']->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    @if ($row['error'])
                                        <td colspan="5" class="text-danger text-center">
                                            <i class="fas fa-exclamation-triangle"></i> {{ $row['error'] }}
                                        </td>
                                    @elseif ($row['merchant_scope_empty'])
                                        <td colspan="5" class="text-warning text-center">
                                            <i class="fas fa-exclamation-triangle"></i> Belum ada merchant yang di-set untuk tenant ini.
                                        </td>
                                    @else
                                        <td class="text-right">{{ number_format($row['customers_total']) }}</td>
                                        <td class="text-right">{{ number_format($row['customers_active']) }}</td>
                                        <td class="text-right">
                                            @if ($row['unpaid_invoices'] > 0)
                                                <span class="badge badge-warning">{{ number_format($row['unpaid_invoices']) }}</span>
                                            @else
                                                0
                                            @endif
                                        </td>
                                        <td class="text-right">Rp {{ number_format($row['revenue_this_month'], 0, ',', '.') }}</td>
                                        <td class="text-right"
                                            title="DPP Rp {{ number_format($row['tax']['dpp'], 0, ',', '.') }} · PPN Rp {{ number_format($row['tax']['ppn'], 0, ',', '.') }} · BHP Rp {{ number_format($row['tax']['bhp'], 0, ',', '.') }} · USO Rp {{ number_format($row['tax']['uso'], 0, ',', '.') }}">
                                            Rp {{ number_format($row['tax']['total_kewajiban'], 0, ',', '.') }}
                                        </td>
                                    @endif
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.tenants.customers', $row['tenant']->id) }}"
                                               class="btn btn-sm btn-info" title="Customers">
                                                <i class="fas fa-users"></i>
                                            </a>
                                            <a href="{{ route('admin.tenants.transactions', $row['tenant']->id) }}"
                                               class="btn btn-sm btn-primary" title="Transactions">
                                                <i class="fas fa-money-bill-wave"></i>
                                            </a>
                                            @unless ($isSupervisor)
                                            <a href="{{ route('admin.tenants.show', $row['tenant']->id) }}"
                                               class="btn btn-sm btn-secondary" title="Detail Tenant">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @endunless
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">Belum ada tenant.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
