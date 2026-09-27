@extends('admin.layouts.app')

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
                    <a href="{{ route('admin.tenants.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-building"></i> Tenant Management
                    </a>
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
                                    <small>Pendapatan Bulan Ini</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-secondary small mb-4">
                        <i class="fas fa-users"></i> Customer Aktif (semua tenant): <strong>{{ number_format($totals['customers_active']) }}</strong>
                        &nbsp;|&nbsp;
                        <i class="fas fa-user-clock"></i> Customer Potential (semua tenant): <strong>{{ number_format($totals['customers_potential']) }}</strong>
                    </div>

                    <!-- Per-tenant breakdown -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover table-sm">
                            <thead class="thead-dark">
                                <tr>
                                    <th width="40">#</th>
                                    <th>Tenant</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-right">Total Customer</th>
                                    <th class="text-right">Customer Aktif</th>
                                    <th class="text-right">Invoice Belum Lunas</th>
                                    <th class="text-right">Pendapatan Bulan Ini</th>
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
                                        @if ($row['tenant']->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    @if ($row['error'])
                                        <td colspan="4" class="text-danger text-center">
                                            <i class="fas fa-exclamation-triangle"></i> {{ $row['error'] }}
                                        </td>
                                    @elseif ($row['merchant_scope_empty'])
                                        <td colspan="4" class="text-warning text-center">
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
                                    <td colspan="8" class="text-center text-muted py-4">Belum ada tenant.</td>
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
