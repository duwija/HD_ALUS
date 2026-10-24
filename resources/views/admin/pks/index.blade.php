@extends('admin.layouts.app')

@section('content')
@php $tgl = fn($d) => $d ? $d->translatedFormat('d M Y') : '-'; @endphp
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
            <h3 class="mb-0"><i class="fas fa-file-signature mr-2"></i> Manajemen PKS</h3>
            <a href="{{ route('admin.pks.create', array_filter(['tenant' => request('tenant')])) }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Tambah PKS
            </a>
        </div>
        <div class="card-body">
            @include('admin.pks._flash')

            {{-- Ringkasan --}}
            <div class="row mb-3">
                @foreach ([
                    ['aktif', 'PKS aktif', 'bg-success', 'fa-check-circle'],
                    ['segera_berakhir', 'Berakhir ≤ ' . \App\PksAgreement::EXPIRING_DAYS . ' hari', 'bg-warning', 'fa-hourglass-half'],
                    ['berakhir', 'Sudah berakhir', 'bg-danger', 'fa-times-circle'],
                ] as [$key, $label, $bg, $icon])
                <div class="col-lg col-md-4 col-6 mb-2">
                    <a href="{{ route('admin.pks.index', array_filter(['tenant' => request('tenant'), 'status' => $key])) }}" class="text-decoration-none">
                        <div class="card text-white {{ $bg }} h-100 mb-0"><div class="card-body py-2">
                            <div class="h4 mb-0">{{ $summary[$key] }}</div><small><i class="fas {{ $icon }}"></i> {{ $label }}</small>
                        </div></div>
                    </a>
                </div>
                @endforeach
                <div class="col-lg col-md-6 col-6 mb-2">
                    <div class="card text-white bg-info h-100 mb-0"><div class="card-body py-2">
                        <div class="h4 mb-0">{{ number_format($summary['kapasitas_aktif'], 0, ',', '.') }} Mbps</div><small><i class="fas fa-network-wired"></i> Kapasitas PKS aktif</small>
                    </div></div>
                </div>
                <div class="col-lg col-md-6 col-12 mb-2">
                    <div class="card text-white bg-secondary h-100 mb-0"><div class="card-body py-2">
                        <div class="h4 mb-0">{{ $summary['tenant_tanpa_pks'] }}</div><small><i class="fas fa-exclamation-triangle"></i> Tenant belum punya PKS</small>
                    </div></div>
                </div>
            </div>

            {{-- Filter --}}
            <form method="GET" class="form-row align-items-end mb-3">
                <div class="col-md-3 mb-2">
                    <label class="small font-weight-bold mb-1">Tenant</label>
                    <select name="tenant" class="form-control form-control-sm">
                        <option value="">Semua tenant</option>
                        @foreach ($tenants as $t)
                            <option value="{{ $t->id }}" {{ (string) request('tenant') === (string) $t->id ? 'selected' : '' }}>{{ $t->app_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label class="small font-weight-bold mb-1">Status</label>
                    <select name="status" class="form-control form-control-sm">
                        <option value="">Semua status</option>
                        @foreach (\App\PksAgreement::STATUS as $key => $s)
                            <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $s['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label class="small font-weight-bold mb-1">Cari No. PKS / NIB</label>
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-3 mb-2">
                    <button class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Filter</button>
                    <a href="{{ route('admin.pks.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover table-sm">
                    <thead class="thead-dark">
                        <tr>
                            <th>Tenant</th>
                            <th>No. PKS</th>
                            <th>NIB</th>
                            <th>Jenis</th>
                            <th>Masa Berlaku</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Adendum</th>
                            <th class="text-right">Titik/POP</th>
                            <th class="text-right">Kapasitas</th>
                            <th class="text-center" width="90">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($agreements as $a)
                        @php $st = \App\PksAgreement::STATUS[$a->status]; @endphp
                        <tr>
                            <td><strong>{{ $a->tenant->app_name ?? 'Tenant #' . $a->tenant_id }}</strong></td>
                            <td>{{ $a->nomor_pks }}</td>
                            <td>{{ $a->nib ?: '-' }}</td>
                            <td>{{ \App\PksAgreement::JENIS[$a->jenis] ?? $a->jenis }}</td>
                            <td>
                                {{ $tgl($a->berlaku_mulai) }} &ndash; {{ $a->effective_end ? $tgl($a->effective_end) : 'tanpa batas' }}
                                @if ($a->effective_end && $a->berlaku_sampai && !$a->effective_end->eq($a->berlaku_sampai))
                                    <br><small class="text-muted">diubah adendum (semula {{ $tgl($a->berlaku_sampai) }})</small>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge badge-{{ $st['badge'] }}">{{ $st['label'] }}</span>
                                @if (in_array($a->status, ['aktif', 'segera_berakhir']) && $a->days_left !== null)
                                    <br><small class="text-muted">{{ $a->days_left }} hari lagi</small>
                                @endif
                            </td>
                            <td class="text-center">{{ $a->addenda->count() ?: '-' }}</td>
                            <td class="text-right">{{ $a->servicePoints->count() }}</td>
                            <td class="text-right">{{ number_format($a->total_capacity, 0, ',', '.') }} Mbps</td>
                            <td class="text-center">
                                <a href="{{ route('admin.pks.show', $a->id) }}" class="btn btn-sm btn-info" title="Detail"><i class="fas fa-eye"></i></a>
                                @if ($a->file_path)
                                    <a href="{{ route('admin.pks.download', ['pks', $a->id]) }}" target="_blank" class="btn btn-sm btn-secondary" title="Lihat dokumen"><i class="fas fa-file-pdf"></i></a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="10" class="text-center text-muted py-4">Belum ada PKS{{ request()->hasAny(['tenant', 'status', 'q']) ? ' yang cocok dengan filter' : '' }}.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
