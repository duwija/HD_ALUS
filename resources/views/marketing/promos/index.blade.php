@extends('layout.main')

@section('title', 'Promo & Pengumuman App')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0"><i class="fas fa-bullhorn mr-2"></i> Promo & Pengumuman App</h4>
                <a href="{{ route('marketing.promos.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus mr-1"></i> Tambah Promo
                </a>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            @endif

            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width:40px">#</th>
                                    <th>Judul</th>
                                    <th>Badge</th>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                    <th>Dibuat</th>
                                    <th style="width:130px">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($promos as $promo)
                                    <tr>
                                        <td>{{ $promo->id }}</td>
                                        <td>
                                            <div class="font-weight-bold">{{ $promo->title }}</div>
                                            <small class="text-muted">{{ Str::limit($promo->content, 60) }}</small>
                                        </td>
                                        <td>
                                            @if($promo->badge)
                                                <span class="badge badge-primary">{{ $promo->badge }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <small>
                                                @if($promo->start_date || $promo->end_date)
                                                    {{ $promo->start_date?->format('d/m/Y') ?? '∞' }}
                                                    –
                                                    {{ $promo->end_date?->format('d/m/Y') ?? '∞' }}
                                                @else
                                                    <span class="text-muted">Permanen</span>
                                                @endif
                                            </small>
                                        </td>
                                        <td>
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox"
                                                       class="custom-control-input toggle-active"
                                                       id="toggle_{{ $promo->id }}"
                                                       data-id="{{ $promo->id }}"
                                                       {{ $promo->is_active ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="toggle_{{ $promo->id }}">
                                                    {{ $promo->is_active ? 'Aktif' : 'Non-aktif' }}
                                                </label>
                                            </div>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $promo->created_by ?? '-' }}<br>
                                                {{ $promo->created_at->format('d M Y') }}
                                            </small>
                                        </td>
                                        <td>
                                            <a href="{{ route('marketing.promos.edit', $promo) }}"
                                               class="btn btn-sm btn-outline-secondary mr-1" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('marketing.promos.destroy', $promo) }}"
                                                  method="POST" class="d-inline"
                                                  onsubmit="return confirm('Hapus promo ini?')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger" title="Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            Belum ada promo. Klik <strong>Tambah Promo</strong> untuk memulai.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($promos->hasPages())
                    <div class="card-footer d-flex justify-content-end">
                        {{ $promos->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
<script>
document.querySelectorAll('.toggle-active').forEach(function(el) {
    el.addEventListener('change', function() {
        var id = this.dataset.id;
        var label = this.nextElementSibling;
        fetch('/marketing/promos/' + id + '/toggle', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            }
        })
        .then(r => r.json())
        .then(data => {
            label.textContent = data.is_active ? 'Aktif' : 'Non-aktif';
        });
    });
});
</script>
@endsection
