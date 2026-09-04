@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">
                        <i class="fas fa-project-diagram"></i> Distribution Router: {{ $tenant->app_name }}
                    </h3>
                    <a href="{{ route('admin.tenants.show', $tenant->id) }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>

                <div class="card-body">
                    @if ($error)
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            Gagal mengambil data router: {{ $error }}
                        </div>
                    @elseif ($distrouterList->isEmpty())
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle"></i> Tenant ini belum memiliki distribution router.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-hover table-bordered mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th width="30">#</th>
                                        <th>Nama</th>
                                        <th>IP</th>
                                        <th class="text-center text-success">Online</th>
                                        <th class="text-center text-secondary">Offline</th>
                                        <th class="text-center text-danger">Disable</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($distrouterList as $i => $dr)
                                    @php $s = $distrouterStats[$dr->id] ?? null; @endphp
                                    <tr id="distrouter-row-{{ $dr->id }}">
                                        <td class="text-muted">{{ $i + 1 }}</td>
                                        <td class="font-weight-bold">{{ $dr->name }}</td>
                                        <td><code class="small">{{ $dr->ip ?? '-' }}</code></td>
                                        <td class="text-center dr-col-online"><span class="font-weight-bold text-success">{{ $s->online ?? 0 }}</span></td>
                                        <td class="text-center dr-col-offline"><span class="font-weight-bold text-secondary">{{ $s->offline ?? 0 }}</span></td>
                                        <td class="text-center dr-col-disabled"><span class="font-weight-bold text-danger">{{ $s->disabled ?? 0 }}</span></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="small text-muted mt-2">
                            <i class="fas fa-info-circle"></i> Online/Offline/Disable diambil langsung dari MikroTik API router (PPPoE active/secret) — cara yang sama dengan widget Distribution Routers di dashboard admin tenant. Angka awal (sebelum berhasil terhubung) adalah estimasi dari status pelanggan di database.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@if($distrouterList->isNotEmpty())
@section('scripts')
<script>
function renderDistrouterRow(id, info) {
    var $row = $('#distrouter-row-' + id);
    $row.find('.dr-col-online').html('<span class="font-weight-bold text-success">' + (info.pppActiveCount || 0) + '</span>');
    $row.find('.dr-col-offline').html('<span class="font-weight-bold text-secondary">' + (info.pppOfflineCount || 0) + '</span>');
    $row.find('.dr-col-disabled').html('<span class="font-weight-bold text-danger">' + (info.pppDisabledCount || 0) + '</span>');
}

function fetchDistrouter(id) {
    var spin = '<i class="fas fa-spinner fa-spin" style="font-size:11px"></i>';
    var $row = $('#distrouter-row-' + id);
    $row.find('.dr-col-online').html(spin);
    $row.find('.dr-col-offline').html(spin);
    $row.find('.dr-col-disabled').html(spin);

    $.ajax({
        url: '{{ url("admin/tenants/{$tenant->id}/routers") }}/' + id + '/info',
        method: 'GET',
        dataType: 'json'
    }).done(function (r) {
        if (r && r.success) {
            renderDistrouterRow(id, r);
        } else {
            $row.find('.dr-col-online').html('<span class="text-danger" title="' + (r.message || 'Tidak Terhubung') + '"><i class="fas fa-times-circle"></i></span>');
            $row.find('.dr-col-offline').html('<span class="font-weight-bold text-secondary">0</span>');
            $row.find('.dr-col-disabled').html('<span class="font-weight-bold text-danger">0</span>');
        }
    }).fail(function () {
        $row.find('.dr-col-online').html('<span class="text-danger" title="Tidak Terhubung"><i class="fas fa-times-circle"></i></span>');
        $row.find('.dr-col-offline').html('<span class="font-weight-bold text-secondary">0</span>');
        $row.find('.dr-col-disabled').html('<span class="font-weight-bold text-danger">0</span>');
    });
}

$(function () {
    @foreach($distrouterList as $dr)
        fetchDistrouter({{ $dr->id }});
    @endforeach
});
</script>
@endsection
@endif
