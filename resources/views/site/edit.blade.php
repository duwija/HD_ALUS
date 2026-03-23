@extends('layout.main')
@section('title','Edit Site')
@section('content')
<div class="container-fluid">
  <div class="row justify-content-center">
    <div class="col-xl-6 col-lg-7 col-12">

  <div class="card card-primary card-outline">
              <div class="card-header">
                <h3 class="card-title font-weight-bold"> Edit Site </h3>
              </div>
              <form role="form" action="{{url ('site')}}/{{ $site->id }}" method="POST">
                @method('patch')
                @csrf
                <div class="card-body">
                  <div class="form-group">
                    <label for="nama">Name</label>
                    <input type="text" disabled="" class="form-control @error('name') is-invalid @enderror " name="name" id="name"  placeholder="Enter site Name" value="{{ $site->name }}">
                     @error('name')
                      <div class="error invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                  <div class="form-group">
                    <label for="location">Location  </label>
                    <input type="text" class="form-control @error('location') is-invalid @enderror" name="location" id="location"  placeholder="Site Location" value="{{ $site->location }}">
                     @error('location')
                      <div class="error invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <div class="form-group">
                    <label for="coordinate"> Coordinate </label>
                  <div class="input-group mb-3">
                    
                    <input type="text" class="form-control @error('coordinate') is-invalid @enderror" name="coordinate"  id="coordinate" placeholder="Coordinate" value="{{ $site->coordinate }}">
                    @error('coordinate')
                      <div class="error invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="input-group-append">
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modal-maps">Get From Maps </button>
                   </div>
                   </div>
                </div>
                  
                  <div class="form-group">
                    <label for="description">Description  </label>
                    <input type="text" class="form-control @error('description') is-invalid @enderror" name="description" id="description" placeholder="site Descrition" value="{{ $site->description }}">
                     @error('description')
                      <div class="error invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                  
                </div>
                <!-- /.card-body -->

                <div class="card-footer">
                  <button type="submit" class="btn btn-primary">Update</button>
                </form>
                  <a href="{{url('site')}}" class="btn btn-secondary  float-right">Cancel</a>
                </div>
              
            </div>
            <!-- /.card -->

    </div><!-- col -->
  </div><!-- row -->
</div><!-- container -->

<!-- Modal OSM -->
<div class="modal fade" id="modal-maps" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Select Location from Map</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div id="map" style="height:400px"></div>
      </div>
      <div class="modal-footer justify-content-end">
        <button type="button" class="btn btn-secondary" id="btn-current-location">
          <i class="fas fa-location-arrow"></i> Current Location
        </button>
        <button type="button" class="btn btn-primary" data-dismiss="modal">Set</button>
      </div>
    </div>
  </div>
</div>

@endsection
@section('footer-scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
<script>
  let map, marker, isMapInitialized = false;

  $('#modal-maps').on('shown.bs.modal', function () {
    if (!isMapInitialized) {
      const existing = document.getElementById('coordinate').value;
      let lat, lng;
      if (existing && existing.includes(',')) {
        const parts = existing.split(',');
        lat = parseFloat(parts[0]) || 0;
        lng = parseFloat(parts[1]) || 0;
      } else {
        const def = "{{ tenant_config('COORDINATE_CENTER', env('COORDINATE_CENTER', '-6.200000,106.816666')) }}".split(',');
        lat = parseFloat(def[0]);
        lng = parseFloat(def[1]);
      }

      map = L.map('map').setView([lat, lng], 15);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
      }).addTo(map);

      marker = L.marker([lat, lng], { draggable: true }).addTo(map);
      marker.on('dragend', function (e) {
        const ll = e.target.getLatLng();
        document.getElementById('coordinate').value = `${ll.lat.toFixed(6)},${ll.lng.toFixed(6)}`;
      });

      L.Control.geocoder({ defaultMarkGeocode: false })
        .on('markgeocode', function (e) {
          const ll = e.geocode.center;
          map.setView(ll, 16);
          marker.setLatLng(ll);
          document.getElementById('coordinate').value = `${ll.lat.toFixed(6)},${ll.lng.toFixed(6)}`;
        }).addTo(map);

      map.on('click', function (e) {
        marker.setLatLng(e.latlng);
        document.getElementById('coordinate').value = `${e.latlng.lat.toFixed(6)},${e.latlng.lng.toFixed(6)}`;
      });

      isMapInitialized = true;
    }
    setTimeout(() => map.invalidateSize(), 300);
  });

  document.getElementById('btn-current-location').addEventListener('click', function () {
    if (!map) return;
    map.locate({ setView: true, maxZoom: 18 });
    map.once('locationfound', function (e) {
      marker.setLatLng(e.latlng);
      document.getElementById('coordinate').value = `${e.latlng.lat.toFixed(6)},${e.latlng.lng.toFixed(6)}`;
    });
    map.once('locationerror', function () {
      alert('Tidak dapat menemukan lokasi Anda.');
    });
  });
</script>
@endsection