@extends('layout.main')
@section('title','Lokasi Absen')
@section('content')
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1><i class="fas fa-map-marker-alt mr-2 text-danger"></i>Lokasi Absen</h1></div>
      <div class="col-sm-6 text-right">
        <a href="/attendance/locations/create" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i>Tambah Lokasi</a>
      </div>
    </div>
  </div>
</section>

<section class="content"><div class="container-fluid">

  <div class="card shadow-sm">
    <div class="card-body p-0">
      <table class="table table-striped table-hover mb-0">
        <thead class="bg-primary text-white">
          <tr>
            <th>#</th>
            <th>Nama Lokasi</th>
            <th>Koordinat</th>
            <th>Radius</th>
            <th>Status</th>
            <th>Absen Tercatat</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($locations as $i => $loc)
          <tr>
            <td>{{ $i+1 }}</td>
            <td>
              <strong>{{ $loc->name }}</strong>
              @if($loc->address)<br><small class="text-muted">{{ $loc->address }}</small>@endif
            </td>
            <td>
              <small>
                <i class="fas fa-map-pin text-danger"></i>
                {{ $loc->latitude }}, {{ $loc->longitude }}<br>
                <a href="https://maps.google.com/?q={{ $loc->latitude }},{{ $loc->longitude }}" target="_blank" class="text-primary">
                  <i class="fas fa-external-link-alt"></i> Google Maps
                </a>
              </small>
            </td>
            <td><span class="badge badge-info">{{ $loc->radius }} m</span></td>
            <td>
              @if($loc->is_active)
                <span class="badge badge-success">Aktif</span>
              @else
                <span class="badge badge-secondary">Nonaktif</span>
              @endif
            </td>
            <td><span class="badge badge-light">{{ $loc->attendances_count }}</span></td>
            <td>
              <a href="/attendance/locations/{{ $loc->id }}/edit" class="btn btn-xs btn-warning"><i class="fas fa-edit"></i></a>
              <form method="POST" action="/attendance/locations/{{ $loc->id }}" class="d-inline"
                    onsubmit="return confirm('Hapus lokasi ini?')">
                @csrf @method('DELETE')
                <button class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></button>
              </form>
            </td>
          </tr>
          @empty
          <tr><td colspan="7" class="text-center text-muted py-4">Belum ada lokasi absen</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- Peta semua lokasi --}}
  <div class="card shadow-sm mt-3">
    <div class="card-header"><b><i class="fas fa-map mr-1"></i>Peta Lokasi</b></div>
    <div class="card-body p-0">
      <div id="map-locations" style="height:400px"></div>
    </div>
  </div>

</div></section>
@endsection

@section('footer-scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
$(function(){
  var map = L.map('map-locations').setView([-6.2,106.8], 12);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

  var locs = @json($locations->where('is_active',true)->values());
  locs.forEach(function(loc){
    L.marker([loc.latitude, loc.longitude])
      .bindPopup('<b>'+loc.name+'</b><br>Radius: '+loc.radius+' m')
      .addTo(map);
    L.circle([loc.latitude, loc.longitude], {radius: loc.radius, color:'#e74c3c', fillOpacity:0.1}).addTo(map);
  });

  if(locs.length > 0) map.setView([locs[0].latitude, locs[0].longitude], 15);
});
</script>
@endsection
