@extends('admin.layouts.app')

@section('content')
@php
    $tgl = fn($d) => $d ? $d->translatedFormat('d M Y') : '-';
    $st = \App\PksAgreement::STATUS[$agreement->status];
    $kb = fn($bytes) => $bytes ? number_format($bytes / 1024 / 1024, 2, ',', '.') . ' MB' : '';
@endphp
<div class="container-fluid">
    @include('admin.pks._flash')

    <div class="card mb-3">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
            <h3 class="mb-0">
                <i class="fas fa-file-signature mr-2"></i> PKS {{ $agreement->nomor_pks }}
                <span class="badge badge-{{ $st['badge'] }} align-middle">{{ $st['label'] }}</span>
            </h3>
            <div>
                <a href="{{ route('admin.pks.index', ['tenant' => $agreement->tenant_id]) }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Daftar PKS</a>
                <a href="{{ route('admin.pks.edit', $agreement->id) }}" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i> Ubah</a>
                <a href="{{ route('admin.pks.create', ['previous' => $agreement->id]) }}" class="btn btn-success btn-sm"><i class="fas fa-redo"></i> Perpanjangan / PKS Baru</a>
                <form action="{{ route('admin.pks.destroy', $agreement->id) }}" method="POST" class="d-inline"
                      onsubmit="return confirm({{ json_encode('Hapus PKS ' . $agreement->nomor_pks . ' beserta adendum & titik layanannya dari daftar?') }})">
                    @csrf @method('DELETE')
                    <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Hapus</button>
                </form>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><th width="170">Tenant</th><td>{{ $agreement->tenant->app_name ?? 'Tenant #' . $agreement->tenant_id }} <small class="text-muted">{{ $agreement->tenant->domain ?? '' }}</small></td></tr>
                        <tr><th>Nomor PKS</th><td>{{ $agreement->nomor_pks }}</td></tr>
                        <tr><th>NIB</th><td>{{ $agreement->nib ?: '-' }}</td></tr>
                        <tr><th>Jenis</th><td>
                            {{ \App\PksAgreement::JENIS[$agreement->jenis] ?? $agreement->jenis }}
                            @if ($agreement->previous)
                                dari <a href="{{ route('admin.pks.show', $agreement->previous->id) }}">{{ $agreement->previous->nomor_pks }}</a>
                            @endif
                        </td></tr>
                        <tr><th>Tanggal PKS</th><td>{{ $tgl($agreement->tanggal_pks) }}</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><th width="170">Berlaku mulai</th><td>{{ $tgl($agreement->berlaku_mulai) }}</td></tr>
                        <tr><th>Berlaku sampai</th><td>
                            {{ $agreement->effective_end ? $tgl($agreement->effective_end) : 'Tanpa batas waktu' }}
                            @if ($agreement->effective_end && $agreement->berlaku_sampai && !$agreement->effective_end->eq($agreement->berlaku_sampai))
                                <small class="text-muted">(diubah adendum, semula {{ $tgl($agreement->berlaku_sampai) }})</small>
                            @endif
                            @if (in_array($agreement->status, ['aktif', 'segera_berakhir']) && $agreement->days_left !== null)
                                <br><small class="text-{{ $agreement->status === 'segera_berakhir' ? 'warning' : 'muted' }}">{{ $agreement->days_left }} hari lagi</small>
                            @endif
                        </td></tr>
                        @if ($agreement->successors->isNotEmpty())
                            <tr><th>Diperpanjang oleh</th><td>
                                @foreach ($agreement->successors as $s)
                                    <a href="{{ route('admin.pks.show', $s->id) }}">{{ $s->nomor_pks }}</a>@if (!$loop->last), @endif
                                @endforeach
                            </td></tr>
                        @endif
                        <tr><th>Dokumen</th><td>
                            @if ($agreement->file_path)
                                <a href="{{ route('admin.pks.download', ['pks', $agreement->id]) }}" target="_blank"><i class="fas fa-file-pdf"></i> {{ $agreement->file_name }}</a>
                                <small class="text-muted">{{ $kb($agreement->file_size) }}</small>
                            @else - @endif
                        </td></tr>
                        <tr><th>Dicatat</th><td><small class="text-muted">{{ $agreement->created_by ?: '-' }}, {{ $agreement->created_at->translatedFormat('d M Y H:i') }}
                            @if ($agreement->updated_by) &middot; diubah {{ $agreement->updated_by }} @endif</small></td></tr>
                    </table>
                </div>
            </div>
            @if ($agreement->keterangan)
                <div class="mt-2 p-2 bg-light rounded"><strong>Keterangan:</strong><br>{!! nl2br(e($agreement->keterangan)) !!}</div>
            @endif
        </div>
    </div>

    {{-- Titik layanan / POP --}}
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-network-wired mr-2"></i> Titik Layanan / POP
                <small class="text-muted">&middot; {{ $agreement->servicePoints->count() }} titik &middot; total {{ number_format($agreement->total_capacity, 0, ',', '.') }} Mbps</small>
            </h5>
            <button class="btn btn-primary btn-sm" type="button" data-toggle="collapse" data-target="#point-add"><i class="fas fa-plus"></i> Tambah</button>
        </div>
        <div class="card-body">
            <div class="collapse {{ $errors->hasAny(['nama', 'kapasitas_mbps', 'jenis', 'koordinat']) ? 'show' : '' }} mb-3" id="point-add">
                <form method="POST" action="{{ route('admin.pks.points.store', $agreement->id) }}" class="border rounded p-2 bg-light">
                    @csrf
                    @include('admin.pks._point_fields', ['point' => null])
                    <button class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Simpan titik layanan/POP</button>
                </form>
            </div>

            @include('admin.pks._map', [
                'mapId' => 'pks-map',
                'markers' => $agreement->servicePoints->map->toMapMarker()->filter()->values()->all(),
                'pickable' => true,
            ])

            <div class="table-responsive">
                <table class="table table-sm table-bordered table-striped mb-0">
                    <thead class="thead-light">
                        <tr><th>Jenis</th><th>Nama</th><th class="text-right">Kapasitas</th><th>Lokasi</th><th>Koordinat</th><th>Keterangan</th><th class="text-center" width="90">Aksi</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($agreement->servicePoints as $p)
                        <tr>
                            <td><span class="badge badge-{{ $p->jenis === 'pop' ? 'primary' : 'info' }}">{{ \App\PksServicePoint::JENIS[$p->jenis] ?? $p->jenis }}</span></td>
                            <td>{{ $p->nama }}</td>
                            <td class="text-right">{{ number_format($p->kapasitas_mbps, 0, ',', '.') }} Mbps</td>
                            <td>{{ $p->lokasi ?: '-' }}</td>
                            <td>
                                @if ($p->lat_lng)
                                    <a href="#pks-map" onclick="window.pksMaps['pks-map'].focus({{ $p->id }}); return false;" title="Lihat di peta">
                                        <i class="fas fa-map-marker-alt"></i> {{ $p->koordinat }}
                                    </a>
                                @else - @endif
                            </td>
                            <td>{!! $p->keterangan ? nl2br(e($p->keterangan)) : '-' !!}</td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-primary point-edit" title="Ubah"
                                        data-action="{{ route('admin.pks.points.update', [$agreement->id, $p->id]) }}"
                                        data-point="{{ json_encode($p->only(['jenis', 'nama', 'kapasitas_mbps', 'lokasi', 'koordinat', 'keterangan'])) }}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form action="{{ route('admin.pks.points.destroy', [$agreement->id, $p->id]) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm({{ json_encode('Hapus ' . $p->nama . '?') }})">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center text-muted">Belum ada titik layanan/POP.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Adendum --}}
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-paperclip mr-2"></i> Adendum <small class="text-muted">&middot; {{ $agreement->addenda->count() }}</small></h5>
            <button class="btn btn-primary btn-sm" type="button" data-toggle="collapse" data-target="#addendum-add"><i class="fas fa-plus"></i> Tambah Adendum</button>
        </div>
        <div class="card-body">
            <div class="collapse {{ $errors->hasAny(['nomor', 'berlaku_sampai_baru']) ? 'show' : '' }} mb-3" id="addendum-add">
                <form method="POST" action="{{ route('admin.pks.addenda.store', $agreement->id) }}" enctype="multipart/form-data" class="border rounded p-2 bg-light">
                    @csrf
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label class="small font-weight-bold">Nomor adendum <span class="text-danger">*</span></label>
                            <input type="text" name="nomor" class="form-control form-control-sm" maxlength="150" required value="{{ old('nomor') }}">
                        </div>
                        <div class="form-group col-md-4">
                            <label class="small font-weight-bold">Tanggal adendum</label>
                            <input type="date" name="tanggal" class="form-control form-control-sm" value="{{ old('tanggal') }}">
                        </div>
                        <div class="form-group col-md-4">
                            <label class="small font-weight-bold">Masa berlaku baru</label>
                            <input type="date" name="berlaku_sampai_baru" class="form-control form-control-sm" value="{{ old('berlaku_sampai_baru') }}">
                            <small class="text-muted">Isi hanya jika adendum mengubah masa berlaku.</small>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="small font-weight-bold">Dokumen adendum</label>
                            <input type="file" name="file" class="form-control-file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                            <small class="text-muted">PDF/JPG/PNG/DOC/DOCX, maks. 20 MB.</small>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="small font-weight-bold">Keterangan / isi perubahan</label>
                            <textarea name="keterangan" class="form-control form-control-sm" rows="2" maxlength="5000">{{ old('keterangan') }}</textarea>
                        </div>
                    </div>
                    <button class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Simpan adendum</button>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-bordered table-striped mb-0">
                    <thead class="thead-light">
                        <tr><th>Nomor</th><th>Tanggal</th><th>Masa berlaku baru</th><th>Keterangan</th><th>Dokumen</th><th class="text-center" width="60">Aksi</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($agreement->addenda as $ad)
                        <tr>
                            <td>{{ $ad->nomor }}</td>
                            <td>{{ $tgl($ad->tanggal) }}</td>
                            <td>{{ $ad->berlaku_sampai_baru ? $tgl($ad->berlaku_sampai_baru) : '-' }}</td>
                            <td>{!! $ad->keterangan ? nl2br(e($ad->keterangan)) : '-' !!}</td>
                            <td>
                                @if ($ad->file_path)
                                    <a href="{{ route('admin.pks.download', ['adendum', $ad->id]) }}" target="_blank"><i class="fas fa-file-pdf"></i> {{ $ad->file_name }}</a>
                                @else - @endif
                            </td>
                            <td class="text-center">
                                <form action="{{ route('admin.pks.addenda.destroy', [$agreement->id, $ad->id]) }}" method="POST"
                                      onsubmit="return confirm({{ json_encode('Hapus adendum ' . $ad->nomor . '?') }})">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted">Belum ada adendum.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Riwayat PKS tenant --}}
    <div class="card mb-3">
        <div class="card-header"><h5 class="mb-0"><i class="fas fa-history mr-2"></i> Riwayat PKS {{ $agreement->tenant->app_name ?? '' }}</h5></div>
        <div class="card-body p-0">
            <table class="table table-sm table-hover mb-0">
                <thead class="thead-light"><tr><th>No. PKS</th><th>Jenis</th><th>Masa Berlaku</th><th class="text-center">Status</th></tr></thead>
                <tbody>
                    @foreach ($history as $h)
                    @php $hs = \App\PksAgreement::STATUS[$h->status]; @endphp
                    <tr class="{{ $h->id === $agreement->id ? 'table-active font-weight-bold' : '' }}">
                        <td><a href="{{ route('admin.pks.show', $h->id) }}">{{ $h->nomor_pks }}</a></td>
                        <td>{{ \App\PksAgreement::JENIS[$h->jenis] ?? $h->jenis }}</td>
                        <td>{{ $tgl($h->berlaku_mulai) }} &ndash; {{ $h->effective_end ? $tgl($h->effective_end) : 'tanpa batas' }}</td>
                        <td class="text-center"><span class="badge badge-{{ $hs['badge'] }}">{{ $hs['label'] }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal ubah titik layanan/POP --}}
<div class="modal fade" id="point-edit-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content" id="point-edit-form">
            @csrf @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">Ubah Titik Layanan / POP</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                @include('admin.pks._point_fields', ['point' => null, 'prefix' => 'edit-'])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function () {
    $('.point-edit').on('click', function () {
        var p = $(this).data('point');
        var $f = $('#point-edit-form').attr('action', $(this).data('action'));
        ['jenis', 'nama', 'kapasitas_mbps', 'lokasi', 'koordinat', 'keterangan'].forEach(function (k) {
            $f.find('[name="' + k + '"]').val(p[k] == null ? '' : p[k]);
        });
        $('#point-edit-modal').modal('show');
    });
});
</script>
@endsection
