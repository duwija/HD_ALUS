@extends('layout.main')
@section('title', $location->id ? 'Edit Lokasi Absen' : 'Tambah Lokasi Absen')
@section('content')
<section class="content-header">
  <div class="container-fluid">
    <h1><i class="fas fa-map-marker-alt text-danger mr-2"></i>
      {{ $location->id ? 'Edit Lokasi' : 'Tambah Lokasi Absen' }}
    </h1>
  </div>
</section>

<section class="content"><div class="container-fluid">
<div class="row">
  <div class="col-md-6">
    <div class="card shadow-sm">
      <div class="card-body">
        <form method="POST" action="{{ $location->id ? '/attendance/locations/'.$location->id : '/attendance/locations' }}">
          @csrf
          @if($location->id) @method('PATCH') @endif
          @if($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{$e}}</li>@endforeach</ul></div>
          @endif

          <div class="form-group">
            <label>Nama Lokasi <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name', $location->name) }}" placeholder="Kantor Pusat" required>
            @error('name')<div class="invalid-feedback">{{$message}}</div>@enderror
          </div>

          <div class="form-group">
            <label>Alamat</label>
            <input type="text" name="address" class="form-control" value="{{ old('address', $location->address) }}" placeholder="Jl. ...">
          </div>

          <div class="row">
            <div class="col">
              <div class="form-group">
                <label>Latitude <span class="text-danger">*</span></label>
                <input type="text" name="latitude" id="inp-lat" class="form-control @error('latitude') is-invalid @enderror"
                       value="{{ old('latitude', $location->latitude) }}" required placeholder="-6.200000">
              </div>
            </div>
            <div class="col">
              <div class="form-group">
                <label>Longitude <span class="text-danger">*</span></label>
                <input type="text" name="longitude" id="inp-lng" class="form-control @error('longitude') is-invalid @enderror"
                       value="{{ old('longitude', $location->longitude) }}" required placeholder="106.816666">
              </div>
            </div>
          </div>

          <div class="form-group">
            <label>Radius Absen (meter) <span class="text-danger">*</span>
              <small class="text-muted">— jarak maksimum dari titik ini yang diizinkan</small>
            </label>
            <input type="number" name="radius" class="form-control" value="{{ old('radius', $location->radius ?? 100) }}" min="10" max="5000" required>
          </div>

          <div class="form-group">
            <label>Catatan</label>
            <textarea name="note" class="form-control" rows="2">{{ old('note', $location->note) }}</textarea>
          </div>

          <div class="form-check mb-3">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" class="form-check-input" name="is_active" value="1" id="chk-active"
              {{ old('is_active', $location->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="chk-active">Lokasi Aktif</label>
          </div>

          <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Simpan</button>
          <a href="/attendance/locations" class="btn btn-secondary ml-1">Batal</a>
        </form>
      </div>
    </div>
  </div>

  {{-- Peta pilih koordinat --}}
  <div class="col-md-6">
    <div class="card shadow-sm">
      <div class="card-header"><b><i class="fas fa-map-pin mr-1 text-danger"></i>Klik peta untuk pilih koordinat</b></div>
      <div class="card-body p-0">
        <div id="pick-map" style="height:400px"></div>
      </div>
    </div>
  </div>
</div>
</div></section>
@endsection

@section('footer-scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
$(function(){
  var _center   = "{{ tenant_config('COORDINATE_CENTER', env('COORDINATE_CENTER', '-6.200000,106.816666')) }}".split(',');
  var defaultLat = parseFloat($('#inp-lat').val()) || parseFloat(_center[0]);
  var defaultLng = parseFloat($('#inp-lng').val()) || parseFloat(_center[1]);
  var map    = L.map('pick-map').setView([defaultLat, defaultLng], 15);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

  var marker = L.marker([defaultLat, defaultLng], {draggable:true}).addTo(map);
  var circle = L.circle([defaultLat, defaultLng], {radius: parseInt($('input[name=radius]').val()) || 100, color:'#e74c3c', fillOpacity:0.15}).addTo(map);

  function updateInputs(lat, lng){
    $('#inp-lat').val(lat.toFixed(7));
    $('#inp-lng').val(lng.toFixed(7));
    marker.setLatLng([lat,lng]);
    circle.setLatLng([lat,lng]);
  }

  map.on('click', function(e){ updateInputs(e.latlng.lat, e.latlng.lng); });
  marker.on('dragend', function(){ var ll = marker.getLatLng(); updateInputs(ll.lat, ll.lng); });
  $('input[name=radius]').on('input', function(){ circle.setRadius(parseInt(this.value)||100); });
});
</script>
@endsection
