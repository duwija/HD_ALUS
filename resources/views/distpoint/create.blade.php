@extends('layout.main')
@section('title','Add New Distribution Point')


@section('content')
<section class="content-header">
  <div class="d-flex justify-content-center">
    <div class="card card-primary card-outline col-md-6 ">
      <div class="card-header">
        <h3 class="card-title font-weight-bold"> Add New Distribution Point </h3>
      </div>
      <form role="form" method="post" action="/distpoint">
        @csrf
        <div class="card-body row">
          <div class="form-group col-md-3">
            <label for="nama">Name</label>
            <input type="text" class="form-control @error('name') is-invalid @enderror " name="name" id="name"  placeholder="Enter Distribution Point Name" value="{{old('name')}}">
            @error('name')
            <div class="error invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="form-group col-md-3">
            <label for="site location">  Location </label>
            <div class="input-group mb-3">
              <select name="id_site" id="id_site" class="form-control select2">
                {{-- <option value="1">none</option> --}}
                @foreach ($site as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
              </select>
            </div>

          </div>
          <div class="form-group col-md-3">
            <label for="ip">Capacity</label>
            <input type="number" class="form-control" name="ip" id="ip"  placeholder="ODP Capacity" value="{{old('ip')}}">

          </div>
          <div class="form-group col-md-3">
            <label for="security">Optic Power</label>
            <input type="number" class="form-control" name="security" id="security"  placeholder="Redaman" value="{{old('security')}}">
          </div>
          <div class="form-group col-md-3">
            <label for="distpointgroup_id"> Group </label>
            <div class="input-group mb-3">
              <select name="distpointgroup_id" id="distpointgroup_id" class="form-control select2">
                @if ($distpointgroup->isEmpty())
                <option disabled selected>No groups available</option>
                @else
                <option value="" disabled selected>Select a group</option>
                @foreach ($distpointgroup as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
                @endif
              </select>
            </div>
          </div>
          <div class="form-group col-md-3">
           <label for="parrent"> Parrent </label>
           <div class="input-group mb-3">
            <select name="parrent" id="parrent" class="form-control select2" disabled>
              <option value="" disabled selected>Select parent</option>
              @foreach ($distpoint as $id => $name)
              <option value="{{ $id }}">{{ $name }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="form-group col-md-6">
          <label for="coordinate">Coordinate</label>
          <div class="input-group mb-3">
           <input type="text"
           class="form-control @error('coordinate') is-invalid @enderror"
           name="coordinate"
           id="coordinate"
           placeholder="Latitude,Longitude"
           value="{{ old('coordinate') }}"
           pattern="^-?\d{1,2}\.\d+,\s*-?\d{1,3}\.\d+$"
           title="Format harus Latitude,Longitude seperti -6.200000,106.816666">

           @error('coordinate')
           <div class="error invalid-feedback">{{ $message }}</div>
           @enderror
           <div class="input-group-append">
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modal-maps">Get From Maps</button>
          </div>
        </div>
      </div>

      <div class="form-group">
        <input type="hidden" name="create_at" value="{{now()}}" >
      </div>

      <div class="form-group col-md-12">
        <label for="description">Description</label>
        <textarea class="form-control @error('description') is-invalid @enderror"
        name="description"
        id="description"
        placeholder="Site Description"
        rows="3">{{ old('description') }}</textarea>
        @error('description')
        <div class="error invalid-feedback">{{ $message }}</div>
        @enderror
      </div>



    </div>
    <!-- /.card-body -->

    <div class="card-footer">
      <button type="submit" class="btn btn-primary">Submit</button>
      <a href="{{url('distpoint')}}" class="btn btn-default float-right">Cancel</a>
    </div>
  </form>
</div>
<!-- /.card -->

<!-- Form Element sizes -->



<!-- Modal -->
<div class="modal fade" id="modal-maps" tabindex="-1" role="dialog" aria-labelledby="modal-mapsLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Select Location from Map</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      
      <div class="modal-body">
        <div id="map" style="height: 400px;"></div>
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

<!-- /.modal -->
</div>
</section>
@endsection
@section('footer-scripts')
<!-- Leaflet CSS & JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<!-- Leaflet Geocoder -->
<link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>

<script>
  let map;
  let marker;
  let isMapInitialized = false;

  $('#modal-maps').on('shown.bs.modal', function () {
    if (!isMapInitialized) {
      const defaultLatLng = "{{ env('COORDINATE_CENTER', '-6.200000,106.816666') }}".split(',');
      const lat = parseFloat(defaultLatLng[0]);
      const lng = parseFloat(defaultLatLng[1]);

      map = L.map('map').setView([lat, lng], 13);

      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
      }).addTo(map);

      // 📌 Marker draggable
      marker = L.marker([lat, lng], { draggable: true }).addTo(map);
      marker.on('dragend', function (e) {
        const latlng = e.target.getLatLng();
        document.getElementById('coordinate').value = `${latlng.lat.toFixed(6)},${latlng.lng.toFixed(6)}`;
      });

      // 🔍 Search bar
      L.Control.geocoder({
        defaultMarkGeocode: false
      })
      .on('markgeocode', function(e) {
        const latlng = e.geocode.center;
        map.setView(latlng, 16);
        marker.setLatLng(latlng);
        document.getElementById('coordinate').value = `${latlng.lat.toFixed(6)},${latlng.lng.toFixed(6)}`;
      })
      .addTo(map);

      // Tandai bahwa peta sudah di-inisialisasi
      isMapInitialized = true;
    }

    setTimeout(() => {
      map.invalidateSize();
    }, 300);
  });

  // 🌍 Gunakan Lokasi Saya
  document.getElementById('btn-current-location').addEventListener('click', function () {
    if (!map) return;

    map.locate({ setView: true, maxZoom: 18 });

    map.once('locationfound', function (e) {
      const { lat, lng } = e.latlng;
      marker.setLatLng(e.latlng);
      document.getElementById('coordinate').value = `${lat.toFixed(6)},${lng.toFixed(6)}`;
    });

    map.once('locationerror', function () {
      alert('Tidak dapat menemukan lokasi Anda. Pastikan izin lokasi aktif di browser.');
    });
  });
</script>
<script>
  $(document).ready(function () {
    $('#distpointgroup_id').on('change', function () {
      const selectedValue = $(this).val();
      const parentSelect = $('#parrent');

      if (selectedValue) {
        parentSelect.prop('disabled', false);
      } else {
        parentSelect.prop('disabled', true).val('');
      }
    });
  });
</script>

@endsection
