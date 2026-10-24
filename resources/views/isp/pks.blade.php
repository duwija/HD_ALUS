@extends('layout.main')
@section('title', 'PKS')

@section('content')
@php $tgl = fn($d) => $d ? $d->translatedFormat('d M Y') : '-'; @endphp
<section class="content-header">
  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title font-weight-bold m-0">
        <i class="fas fa-file-signature"></i> Perjanjian Kerja Sama (PKS) &middot; {{ tenant_name() ?: config('app.name') }}
      </h3>
    </div>

    <div class="card-body">
      @forelse ($agreements as $a)
      @php $st = \App\PksAgreement::STATUS[$a->status]; $current = $a->status !== 'diganti'; @endphp
      <div class="card {{ $current ? 'card-outline card-' . ($st['badge'] === 'secondary' ? 'primary' : $st['badge']) : 'collapsed-card' }} mb-3">
        <div class="card-header">
          <h3 class="card-title">
            <strong>{{ $a->nomor_pks }}</strong>
            <span class="badge badge-{{ $st['badge'] }} ml-1">{{ $st['label'] }}</span>
            <small class="text-muted ml-2">{{ \App\PksAgreement::JENIS[$a->jenis] ?? $a->jenis }}
              &middot; {{ $tgl($a->berlaku_mulai) }} &ndash; {{ $a->effective_end ? $tgl($a->effective_end) : 'tanpa batas' }}
              @if (in_array($a->status, ['aktif', 'segera_berakhir']) && $a->days_left !== null) ({{ $a->days_left }} hari lagi) @endif
            </small>
          </h3>
          <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-{{ $current ? 'minus' : 'plus' }}"></i></button>
          </div>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <table class="table table-sm table-borderless mb-2">
                <tr><th width="160">Nomor PKS</th><td>{{ $a->nomor_pks }}</td></tr>
                <tr><th>NIB</th><td>{{ $a->nib ?: '-' }}</td></tr>
                <tr><th>Tanggal PKS</th><td>{{ $tgl($a->tanggal_pks) }}</td></tr>
                @if ($a->previous)
                  <tr><th>Perpanjangan dari</th><td>{{ $a->previous->nomor_pks }}</td></tr>
                @endif
              </table>
            </div>
            <div class="col-md-6">
              <table class="table table-sm table-borderless mb-2">
                <tr><th width="160">Masa berlaku</th><td>
                  {{ $tgl($a->berlaku_mulai) }} &ndash; {{ $a->effective_end ? $tgl($a->effective_end) : 'tanpa batas' }}
                  @if ($a->effective_end && $a->berlaku_sampai && !$a->effective_end->eq($a->berlaku_sampai))
                    <br><small class="text-muted">diubah adendum (semula {{ $tgl($a->berlaku_sampai) }})</small>
                  @endif
                </td></tr>
                <tr><th>Dokumen</th><td>
                  @if ($a->file_path)
                    <a href="{{ url('isp/pks/file/pks/' . $a->id) }}" target="_blank"><i class="fas fa-file-pdf"></i> {{ $a->file_name }}</a>
                  @else - @endif
                </td></tr>
                <tr><th>Kapasitas total</th><td>{{ number_format($a->total_capacity, 0, ',', '.') }} Mbps ({{ $a->servicePoints->count() }} titik)</td></tr>
              </table>
            </div>
          </div>
          @if ($a->keterangan)
            <div class="p-2 bg-light rounded mb-3"><strong>Keterangan:</strong><br>{!! nl2br(e($a->keterangan)) !!}</div>
          @endif

          <h6 class="font-weight-bold"><i class="fas fa-network-wired"></i> Titik Layanan / POP</h6>
          @include('admin.pks._map', [
            'mapId' => 'pks-map-' . $a->id,
            'markers' => $a->servicePoints->map->toMapMarker()->filter()->values()->all(),
            'pickable' => false,
          ])
          <div class="table-responsive mb-3">
            <table class="table table-sm table-bordered table-striped mb-0">
              <thead class="thead-light"><tr><th>Jenis</th><th>Nama</th><th class="text-right">Kapasitas</th><th>Lokasi</th><th>Koordinat</th><th>Keterangan</th></tr></thead>
              <tbody>
                @forelse ($a->servicePoints as $p)
                <tr>
                  <td><span class="badge badge-{{ $p->jenis === 'pop' ? 'primary' : 'info' }}">{{ \App\PksServicePoint::JENIS[$p->jenis] ?? $p->jenis }}</span></td>
                  <td>{{ $p->nama }}</td>
                  <td class="text-right">{{ number_format($p->kapasitas_mbps, 0, ',', '.') }} Mbps</td>
                  <td>{{ $p->lokasi ?: '-' }}</td>
                  <td>
                    @if ($p->lat_lng)
                      <a href="#" onclick="window.pksMaps['pks-map-{{ $a->id }}'].focus({{ $p->id }}); return false;" title="Lihat di peta">
                        <i class="fas fa-map-marker-alt"></i> {{ $p->koordinat }}
                      </a>
                    @else - @endif
                  </td>
                  <td>{!! $p->keterangan ? nl2br(e($p->keterangan)) : '-' !!}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted">Belum ada titik layanan/POP.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <h6 class="font-weight-bold"><i class="fas fa-paperclip"></i> Adendum</h6>
          <div class="table-responsive">
            <table class="table table-sm table-bordered table-striped mb-0">
              <thead class="thead-light"><tr><th>Nomor</th><th>Tanggal</th><th>Masa berlaku baru</th><th>Keterangan</th><th>Dokumen</th></tr></thead>
              <tbody>
                @forelse ($a->addenda as $ad)
                <tr>
                  <td>{{ $ad->nomor }}</td>
                  <td>{{ $tgl($ad->tanggal) }}</td>
                  <td>{{ $ad->berlaku_sampai_baru ? $tgl($ad->berlaku_sampai_baru) : '-' }}</td>
                  <td>{!! $ad->keterangan ? nl2br(e($ad->keterangan)) : '-' !!}</td>
                  <td>
                    @if ($ad->file_path)
                      <a href="{{ url('isp/pks/file/adendum/' . $ad->id) }}" target="_blank"><i class="fas fa-file-pdf"></i> {{ $ad->file_name }}</a>
                    @else - @endif
                  </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted">Tidak ada adendum.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
      @empty
      <div class="text-center text-muted py-5">
        <i class="fas fa-file-signature fa-3x mb-3"></i><br>
        Belum ada PKS yang tercatat untuk {{ tenant_name() ?: config('app.name') }}.
      </div>
      @endforelse
    </div>

    <div class="card-footer small text-muted">
      Data PKS dikelola oleh pengelola platform. Hubungi admin bila ada data yang perlu diperbarui.
    </div>
  </div>
</section>
@endsection
