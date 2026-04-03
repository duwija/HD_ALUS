@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0"><i class="fas fa-id-card mr-2"></i> License Plans</h3>
                    <a href="{{ route('admin.license-plans.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Tambah Plan
                    </a>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle"></i> {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead class="thead-dark">
                                <tr>
                                    <th width="5%">#</th>
                                    <th>Nama Plan</th>
                                    <th>Maks. Pelanggan</th>
                                    <th>Harga/Bulan</th>
                                    <th>Deskripsi</th>
                                    <th>Urutan</th>
                                    <th>Status</th>
                                    <th>Jumlah Tenant</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($plans as $plan)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td><strong>{{ $plan->name }}</strong></td>
                                    <td>
                                        @if($plan->isUnlimited())
                                            <span class="badge badge-primary"><i class="fas fa-infinity mr-1"></i> Unlimited</span>
                                        @else
                                            {{ number_format($plan->max_customers) }}
                                        @endif
                                    </td>
                                    <td>{{ $plan->priceFormatted() }}</td>
                                    <td><small class="text-muted">{{ $plan->description ?: '-' }}</small></td>
                                    <td>{{ $plan->sort_order }}</td>
                                    <td>
                                        @if($plan->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>{{ $plan->tenants()->count() }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('admin.license-plans.edit', $plan->id) }}"
                                           class="btn btn-sm btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.license-plans.destroy', $plan->id) }}"
                                              method="POST" class="d-inline"
                                              onsubmit="return confirm('Hapus plan ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        <i class="fas fa-id-card fa-2x mb-2 d-block"></i>
                                        Belum ada license plan. <a href="{{ route('admin.license-plans.create') }}">Tambah sekarang</a>.
                                    </td>
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
