@extends('layout.main')
@section('title', 'Site')

@section('maps')
@inject('olt', 'App\Olt')

<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
@endsection

@section('content')
<section class="content-header">
  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title font-weight-bold">Show Site</h3>
    </div>

    <div class="card-body">
      <div class="col-md-12 card">
        <div class="form-group col-md-4">
          <label class="font-weight-bold">Name:</label>
          <span class="ml-2">{{ $site->name }}</span>
        </div>
        <div class="form-group col-md-8">
          <label class="font-weight-bold">Location:</label>
          <span class="ml-2">{{ $site->name }}</span>
        </div>

        <div class="flex">
         <!--  <div class="form-group col-md-4">
            <a href="https://www.google.com/maps/place/{{ $site->coordinate }}" target="_blank" class="btn btn-info btn-sm">
              <i class="fa fa-map"></i> Show in Google Maps
            </a>
          </div> -->
          <div class="form-group float-right m-2">
            <a href="/site/{{ $site->id }}/edit" class="btn btn-primary btn-sm">Edit</a>
            <form action="/site/{{ $site->id }}" method="POST" class="d-inline site-delete">
              @method('delete')
              @csrf
              <button type="submit" class="btn btn-danger btn-sm">Delete</button>
            </form>
          </div>
        </div>
      </div>

      <div class=" col-md-12 card card-primary card-outline">
        <div class="form-group">
          <label for="maps">Maps   </label>

          @if ($site->coordinate == null)

          <br><a class="p-md-2">No Map set !!</a> 

          @else
          <div>

          </div>
          <div class="float-right " >
            <a href="https://www.google.com/maps/place/{{ $site->coordinate }}" target="_blank" class="btn btn-info btn-sm "><i  class="fa fa-map"> </i> Show in Google Maps </a>



          </div>
          <div style="width: 100%; height: 500px;"id="map">Map Not Set !!</div> @endif
        </div>






        <div>

          {{--   @foreach( $distpoint_chart as $distpoint_chart)

          @endforeach
        </div>
        --}}







      </div>
    </div>
    <!-- /.card-body -->
  </div>
  <!-- /.card -->
</section>
@endsection

@section('footer-scripts')
<script type="text/javascript">
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  $(document).ready(function() {
    $('.ont-status').each(function() {
      var id_onu = $(this).data('id-onu');
      var id_olt = $(this).data('id-olt');
      var ont_status_id = $(this).find('span').attr('id');

      if (id_onu && id_olt && ont_status_id) {
        $.ajax({
          url: '/olt/ont_status',
          method: 'post',
          data: {
            id_onu: id_onu,
            id_olt: id_olt
          },
          success: function(data) {
            $('#' + ont_status_id).html(data);
          },
          error: function(xhr, status, error) {
            console.log('Error: ' + error);
          }
        });
      } else {
        console.log('Data attributes are missing or invalid');
      }
    });
  });


  var center = @json($center);
  var locations = @json($locations);

  // Ubah koordinat pusat menjadi array [lat, lng]
  var coordinates = center.coordinate.split(',').map(Number);

  // Inisialisasi peta
  var map = L.map('map').setView(coordinates, center.zoom);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '© OpenStreetMap contributors'
  }).addTo(map);

  // Menyimpan marker dalam array
  var markers = []; // Array untuk menyimpan marker

  // Iterasi lokasi untuk menambahkan marker dan garis
  locations.forEach(location => {
    var coords = location.customer.split(',').map(Number); // Ubah string koordinat jadi [lat, lng]
    var parentCoords = location.parrent ? location.parrent.split(',').map(Number) : coords; // Koordinat parent
    var marker;

    // Tentukan ikon untuk marker
    var icon = L.icon({
      iconUrl: location.icon,
      iconSize: location.icon.includes('pop1.png') ? [48, 48] : [32, 32], // Perbesar ukuran ikon utama
      iconAnchor: [16, 32],
      popupAnchor: [0, -32]
    });

    // Tambahkan marker ke peta
    marker = L.marker(coords, { icon: icon }).addTo(map)
    .bindPopup(`<b>${location.name}</b>`);

    // Tambahkan garis dari parent ke marker ini jika parent ada
    if (parentCoords) {
      L.polyline([parentCoords, coords], { color: 'blue', weight: 2 }).addTo(map);
    }

    // Simpan marker dalam array untuk pencarian
    markers.push(marker);
  });

  // Menambahkan fungsi pencarian
  var searchControl = new L.Control.Search({
    layer: L.layerGroup(markers), // Layer yang berisi semua marker
    initial: false, // Tidak menampilkan hasil pencarian langsung
    zoom: 12, // Zoom ke lokasi hasil pencarian
    marker: false, // Tidak menambahkan marker pencarian
    moveToLocation: function(latlng) { // Ketika lokasi ditemukan
    map.setView(latlng, 15); // Zoom ke lokasi yang ditemukan
  }
});

// Tambahkan kontrol pencarian ke peta
  map.addControl(searchControl);

</script>
@endsection