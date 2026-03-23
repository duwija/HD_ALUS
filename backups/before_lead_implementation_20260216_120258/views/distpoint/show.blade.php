@extends('layout.main')
@section('title',' Distribution Point')
@section('maps')
@inject('olt', 'App\Olt')

@endsection
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript">
  google.charts.load('current', {packages:["orgchart"]});
  google.charts.setOnLoadCallback(drawChart);

  function drawChart() {
    var data = new google.visualization.DataTable();
    data.addColumn('string', 'Name');
    data.addColumn('string', 'Manager');
    data.addColumn('string', 'ToolTip');
    
    const chartData = [];
    @foreach ($distpoint_chart as $dp)
      @php
        $customerCount = \App\Customer::where('id_distpoint', $dp->id)->count();
        $capacity = $dp->ip ?: 0;
        $percentage = $capacity ? round(($customerCount / $capacity) * 100, 1) : 0;
        
        if ($percentage <= 69) {
          $utilizationColor = '#10b981'; // green
        } elseif ($percentage <= 89) {
          $utilizationColor = '#f59e0b'; // orange
        } else {
          $utilizationColor = '#ef4444'; // red
        }
      @endphp
      chartData.push([
        { 
          v: '{{ $dp->id }}', 
          f: `<div style="line-height: 1.3;">
                <div style="font-size: 12px; font-weight: 700; margin-bottom: 6px; color: #111827;">
                  <i class="fas fa-network-wired" style="color: #667eea; font-size: 10px;"></i> {!! $dp->name !!}
                </div>
                @if($dp->id == $distpoint->id)
                <div style="background: #fbbf24; color: #000; padding: 2px 8px; border-radius: 6px; font-size: 9px; display: inline-block; margin: 2px 0; font-weight: 700;">
                  <i class="fas fa-star" style="font-size: 8px;"></i> Selected
                </div>
                @endif
                <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #f3f4f6;">
                  <div style="font-size: 10px; font-weight: 600; margin-bottom: 4px; color: #374151;">
                    <i class="fas fa-users" style="color: #667eea; font-size: 9px;"></i> {{ $customerCount }}/{{ $capacity }} ports
                  </div>
                  <span style="background: {{ $utilizationColor }}; color: white; padding: 3px 10px; border-radius: 8px; font-weight: 700; font-size: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    {{ $percentage }}%
                  </span>
                </div>
              </div>` 
        },
        {!! json_encode((string) $dp->parrent) !!},
        {!! json_encode($dp->name . ' - ' . $customerCount . '/' . $capacity . ' ports (' . $percentage . '%)') !!}
      ]);
    @endforeach
    
    data.addRows(chartData);
    var chart = new google.visualization.OrgChart(document.getElementById('chart_div_distpoint'));
    chart.draw(data, {allowHtml: true, nodeClass: 'modern-orgchart-node', size: 'small'});

    google.visualization.events.addListener(chart, 'select', function() {
      var selection = chart.getSelection();
      if (selection.length > 0) {
        var selectedItem = selection[0];
        if (selectedItem) {
          var distpointId = data.getValue(selectedItem.row, 0);
          window.location.href = '/distpoint/' + distpointId;
        }
      }
    });
  }
</script>



@section('content')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title font-weight-bold"> Show Detail Distribution Point </h3>
    </div>
    
    <div class="card-body">

      <div class="row">
        {{-- Detail ODP --}}
        <div class="col-lg-4 col-md-6 col-12 mb-4">
          <div class="card shadow rounded-4 h-100 border-0">
            <div class="card-header bg-success text-white rounded-top">
              <h5 class="mb-0 ">Detail ODP</h5>
            </div>

            <div class="card-body">
              <div class="row mb-3">
                <div class="col-6">
                  <label class="text-muted mb-0">Nama ODP</label>
                  <div class="font-weight-bold">{{ $distpoint->name }}</div>
                </div>
                <div class="col-6">
                  <label class="text-muted mb-0">Lokasi Site</label>
                  <div class="font-weight-bold">{{ $site->name }}</div>
                </div>
              </div>

              <div class="row mb-3">
                <div class="col-6">
                  <label class="text-muted mb-0">Kapasitas</label>
                  @php
                  $odp_capacity = empty($distpoint->ip) ? '0' : $distpoint->ip;
                  @endphp
                  <div class="font-weight-bold"> {{ $customer_count }}/{{ $odp_capacity }}</div>

                  @php
                  // Hitung persentase berdasarkan customer_count dan ip
                  if ($odp_capacity > 0) {
                    $percentage = ($customer_count / $odp_capacity) * 100;
                  } else {
                    $percentage = 0;
                  }

                  // Tentukan warna progress bar berdasarkan persentase
                  if ($percentage <= 69) {
                    $progressClass = 'bg-success';
                  } elseif ($percentage >= 70 && $percentage <= 89) {
                    $progressClass = 'bg-warning';
                  } else {
                    $progressClass = 'bg-danger';
                  }
                  @endphp

                  <div class="progress">
                    <div class="progress-bar {{ $progressClass }}" role="progressbar" style="width: {{ $percentage }}%" aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100">
                     ({{ number_format($percentage, 2) }}%)
                   </div>
                 </div>

               </div>
               <div class="col-6">
                <label class="text-muted mb-0">Optical Power</label>
                <div class="font-weight-bold">{{ $distpoint->security }}</div>
              </div>
            </div>

            <div class="row mb-3">
              <div class="col-6">
                <label class="text-muted mb-0">Group</label>
                <div>
                  @if($distpoint->group)
                  <a href="{{ url('/distpointgroup/' . $distpoint->group->id) }}" class="font-weight-bold text-primary">
                    {{ $distpoint->group->name }}
                  </a>
                  @else
                  <span class="text-muted font-italic">Tidak ada group</span>
                  @endif
                </div>
              </div>
              <div class="col-6">
                <label class="text-muted mb-0">Parent</label>
                <div>
                  @if ($distpoint->parrent && $distpoint_name)
                  <a href="{{ url('/distpoint/' . $distpoint->parrent) }}" class="font-weight-bold text-primary">
                    {{ $distpoint_name->name }}
                  </a>
                  @else
                  <span class="text-muted font-italic">None</span>
                  @endif
                </div>
              </div>
            </div>

            <div class="form-group">
              <label class="text-muted mb-1">Deskripsi</label>
              <textarea class="form-control rounded shadow-sm" rows="3" disabled>{{ $distpoint->description }}</textarea>
            </div>
          </div>

          <div class="card-footer">
           <a href="/distpoint/{{ $distpoint->id }}/edit" class="btn btn-primary btn-sm ">  Edit  </a>

           <form  action="/distpoint/{{ $distpoint->id }}" method="POST" class="d-inline item-delete" >
            @method('delete')
            @csrf

            <button type="submit"  class="btn btn-danger btn-sm float-right">  Delete  </button>
          </form>


        </div>
      </div>
    </div>

    {{-- Informasi Group --}}
    @if($distpoint->group)
    <div class=" col-md-8 mb-4">
      <div class="card shadow-lg rounded-4 h-100 border-info">
        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center rounded-top">
          <h5 class="mb-0">
            <i class="fas fa-users-cog"></i> Informasi Group {{ optional($distpoint->group)->name ?? 'none' }}
          </h5>
        </div>
        <div class="card-body">
          <div class="row col-md-12">
            <div class="mb-4 col-md-4">
              <strong>Kapasitas </strong>
              <!-- Progress bar for capacity -->
              <div class="font-weight-bold">  {{ $customer_group_count }}/{{ $distpoint->group->capacity }} </div>
              <div class="progress mb-2">
               @php
               $percentage = ($customer_group_count / $distpoint->group->capacity) * 100;
               if ($percentage <= 69) {
                $progressClass = 'bg-success';
              } elseif ($percentage >= 70 && $percentage <= 89) {
                $progressClass = 'bg-warning';
              } else {
                $progressClass = 'bg-danger';
              }
              @endphp

              <div class="progress-bar {{ $progressClass }}" role="progressbar" style="width: {{ $percentage }}%" aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100">
               ({{ number_format($percentage, 2) }}%)
             </div>
           </div>

         </div>

         <div class="mb-3 col-md-4">
          <strong>Jumlah ODP</strong>
          <!-- Icon for Distpoint Count -->
          <div class="d-flex align-items-center">

            <span class="btn btn-sm bg-secondary p-1"> <i class="fas fa-network-wired p-1">  </i>  {{ $group_distpoint_count }}</span>
          </div>
        </div>

        <div class="mb-3 col-md-4">
          <strong>Total Port ODP</strong>
          <!-- Icon for total capacity -->
          <div class="d-flex align-items-center">

            <span class="btn btn-sm badge-primary text-white  p-1"><i class="fas fa-plug p-1"></i>{{ $group_total_capacity }}</span>
          </div>
        </div>
      </div>

      <div class="card-body p-2">
       <div id="chart_div_distpoint" class="table-responsive" style="overflow-x: auto; white-space: nowrap; padding: 30px; background: #f8f9fa; border-radius: 12px;">
        <div style="min-width: max-content;">
          {{-- Chart akan dirender di sini --}}
        </div>
      </div>

    </div>

  </div>
  <div class="card-footer">
    <a href="/distpointgroup/{{ $distpoint->group->id }}/edit" class="btn btn-primary btn-sm ">  Edit  </a>

    <form  action="/distpointgroup/{{ $distpoint->group->id }}" method="POST" class="d-inline item-delete" >
      @method('delete')
      @csrf

      <button type="submit"  class="btn btn-danger btn-sm float-right">  Delete  </button>
    </form>


  </div>
</div>
</div>
@endif



</div>






<div class=" col-md-12 card card-primary card-outline">
  <div class="card-header">
    <h5 class="mb-0 font-weight-bold text-center">Customer Member {{ $distpoint->name }}</h5>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table id="example1" class="table table-bordered table-striped text-xs">

        <thead >
          <tr>
            <th scope="col">#</th>
            <th scope="col">CID</th>
            <th scope="col">Status</th>

            <th scope="col">Name</th>
            <th scope="col">OLT</th>
            <th scope="col">ONU ID</th>
            <th scope="col">Power</th>
            <th scope="col">Plan</th>
            <th scope="col">Address</th>
          </tr>
        </thead>
        <tbody>
          @foreach( $distpoint->customer as $customer)
          <tr>
            <th scope="row">{{ $loop->iteration }}</th>
            <td>
              <a class="btn btn-primary btn-sm" href="/customer/{{$customer->id}}"> {{ $customer->customer_id }}</a></td>
              @php
              $badge_sts = "badge-warning"; // default
              
              if ($customer->status_name && $customer->status_name->name == 'Active')
              $badge_sts = "badge-success";
              elseif ($customer->status_name && $customer->status_name->name == 'Inactive')
              $badge_sts = "badge-secondary";
              elseif ($customer->status_name && $customer->status_name->name == 'Block')
              $badge_sts = "badge-danger";
              elseif ($customer->status_name && $customer->status_name->name == 'Company_Properti')
              $badge_sts = "badge-primary";

              @endphp


              <td class="text-center"><a class="badge text-white {{$badge_sts}}">{{ $customer->status_name->name ?? 'N/A' }}</a></td>

              <td >
               {{ $customer->name }}

             </td>
             <td >
              {{ $customer->olt_name->name ?? 'N/A' }}

            </td><td >
             {{ $customer->id_onu }}

           </td>
           <td class="ont-status" data-id-onu="{{ $customer->id_onu }}" data-id-olt="{{ $customer->id_olt }}">
            <span id="ont_status_{{ $customer->id }}">Loading...</span>
          </td>
          <td >
           {{ $customer->plan_name->name ?? 'N/A' }} ({{ $customer->plan_name ? number_format($customer->plan_name->price) : 'N/A' }})

         </td>
         <td >
           {{ $customer->address }}

         </td>


         <!-- /.modal -->






       </tr>




       @endforeach

     </tbody>
   </table>
 </div>
</div>
</div>







<!-- Map Container -->
<div class="card card-primary card-outline">
  <div class="form-group">
    <label for="maps"></label>

    @if ($distpoint->coordinate == null)
    <br><a class="p-md-2">No Map set !!</a> 
    @else
    <div id="map" style="width: 100%; height: 700px; position: relative;"></div>
    @endif
  </div>
</div>
<!-- /.card-body -->








</div>
<!-- /.card -->

<!-- Form Element sizes -->


















</div>

</section>

@endsection


@section('footer-scripts')

<style>


  /* Node yang sedang terpilih */
  .google-visualization-orgchart-node .selected-node {
    background-color: #d1ecf1 !important;
    padding: 10px;
    border-radius: 0px;
    color: #e33939;
    font-weight: bold;
  }
</style>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.Default.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />


<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster/dist/leaflet.markercluster.js"></script>
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
<link href="https://unpkg.com/@geoman-io/leaflet-geoman-free@2.13.0/dist/leaflet-geoman.css" rel="stylesheet">
<script src="https://unpkg.com/@geoman-io/leaflet-geoman-free@2.13.0/dist/leaflet-geoman.min.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet-search/dist/leaflet-search.min.css" />
<script src="https://unpkg.com/leaflet-search/dist/leaflet-search.min.js"></script>



<script type="text/javascript">
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  $(document).ready(function() {
  // Loop untuk setiap baris dengan class 'ont-status'
    $('.ont-status').each(function() {
    // Ambil data dari elemen tersebut
      var id_onu = $(this).data('id-onu');
      var id_olt = $(this).data('id-olt');
      var ont_status_id = $(this).find('span').attr('id');

    // Pastikan id_onu, id_olt, dan ont_status_id tidak undefined
      if (id_onu && id_olt && ont_status_id) {
      // Panggil AJAX
        $.ajax({
          url: '/olt/ont_status',
          method: 'post',
          data: {
            id_onu: id_onu,
            id_olt: id_olt
          },
          success: function(data) {
          $('#' + ont_status_id).html(data); // Update HTML dengan data yang diterima
        },
        error: function(xhr, status, error) {
          console.log('Error: ' + error); // Handle error jika ada
        }
      });
      } else {
        console.log('Data attributes are missing or invalid');
      }
    });
  });





</script>

<style>
  /* Label untuk Customer dan ODP pada Map */
  .customer-label {
    background: #3b82f6 !important;
    color: white !important;
    border: none !important;
    border-radius: 3px !important;
    padding: 2px 6px !important;
    font-size: 9px !important;
    font-weight: 600 !important;
    box-shadow: 0 1px 3px rgba(0,0,0,0.12) !important;
    white-space: nowrap !important;
  }

  .customer-label:before {
    border-top-color: #3b82f6 !important;
  }

  .odp-label {
    background: #ef4444 !important;
    color: white !important;
    border: none !important;
    border-radius: 3px !important;
    padding: 2px 6px !important;
    font-size: 9px !important;
    font-weight: 600 !important;
    box-shadow: 0 1px 3px rgba(0,0,0,0.12) !important;
    white-space: nowrap !important;
  }

  .odp-label:before {
    border-top-color: #ef4444 !important;
  }

  /* Modern Card Style untuk Google OrgChart */
  .google-visualization-orgchart-node,
  .modern-orgchart-node,
  .google-visualization-orgchart-node-large {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
    background: white !important;
    border: 2px solid #d1d5db !important;
    border-radius: 8px !important;
    padding: 10px 14px !important;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08) !important;
    transition: all 0.3s ease !important;
    color: #1f2937 !important;
    font-weight: 600 !important;
    font-size: 11px !important;
    min-width: 130px !important;
    text-align: center !important;
    position: relative !important;
    overflow: visible !important;
  }

  .google-visualization-orgchart-node:before,
  .modern-orgchart-node:before,
  .google-visualization-orgchart-node-large:before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 5px;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    border-radius: 8px 8px 0 0;
  }

  .google-visualization-orgchart-node:hover,
  .modern-orgchart-node:hover,
  .google-visualization-orgchart-node-large:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
  }

  .google-visualization-orgchart-table {
    border-spacing: 20px 35px !important;
  }

  .google-visualization-orgchart-lineleft,
  .google-visualization-orgchart-lineright,
  .google-visualization-orgchart-linebottom {
    border-color: #cbd5e1 !important;
    border-width: 2px !important;
  }
</style>

<script>
  const iconRed = new L.Icon({
    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
    shadowUrl: 'https://unpkg.com/leaflet@1.9.3/dist/images/marker-shadow.png',
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41]
  });

  const iconBlue = new L.Icon({
    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-blue.png',
    shadowUrl: 'https://unpkg.com/leaflet@1.9.3/dist/images/marker-shadow.png',
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41]
  });
  // Ambil data dari server
  var center = @json($center);
  var locations = @json($locations);
  var coordinates = center.coordinate.split(',').map(Number);

  // Inisialisasi map
  var map = L.map('map').setView(coordinates, center.zoom);

  // Tambahkan tile layer OSM
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '© OpenStreetMap contributors'
  }).addTo(map);

  // Tambahkan search/geocoder control
  L.Control.geocoder({
    defaultMarkGeocode: false
  })
  .on('markgeocode', function(e) {
    var latlng = e.geocode.center;
    map.setView(latlng, 16);
    L.marker(latlng).addTo(map)
    .bindPopup("Hasil pencarian").openPopup();
  })
  .addTo(map);

  // Tombol "Show in Google Maps"
  var googleMapsButton = L.control({ position: 'topright' });
  googleMapsButton.onAdd = function () {
    var div = L.DomUtil.create('div', 'leaflet-bar leaflet-control leaflet-control-custom');
    div.innerHTML = `
    <a 
    href="https://www.google.com/maps/place/{{ $distpoint->coordinate }}" 
    target="_blank" 
    title="Lihat di Google Maps"
    style="
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    background-color: #007bff;
    color: white;
    font-size: 16px;
    text-decoration: none;
    "
    >
    <i class="fa fa-map"></i>
    </a>
    `;
    return div;
  };
  googleMapsButton.addTo(map);

  // Marker utama + koneksi garis
  var mainMarker = null;
  var mainCoords = null;
  locations.forEach(location => {

    const coords = location.coordinate.split(',').map(Number);

  let icon = iconBlue; // default untuk customer
  if (location.type === 'distpoint') {
    icon = iconRed;
  }

  const marker = L.marker(coords, { icon }).addTo(map)
  .bindPopup(`${location.name}`);

  // Tambahkan label nama yang selalu tampil
  if (location.type === 'customer') {
    marker.bindTooltip(location.name, {
      permanent: true,
      direction: 'top',
      className: 'customer-label',
      offset: [0, -30]
    });
  } else {
    marker.bindTooltip(location.name, {
      permanent: true,
      direction: 'top',
      className: 'odp-label',
      offset: [0, -30]
    });
  }

  // Tarik garis ke parent jika tersedia
  if (location.parent_coordinate) {
    const parentCoords = location.parent_coordinate.split(',').map(Number);

    let polylineOptions = {
      color: 'blue',
      weight: 1,
      dashArray: '4'
    };

    if (location.type === 'distpoint') {
      polylineOptions = {
        color: 'orange',
        weight: 2
      // Tidak pakai dashArray → garis solid
      };
    }

    L.polyline([parentCoords, coords], polylineOptions).addTo(map);
  }

});

  // Klik kanan untuk melihat koordinat
  map.on('contextmenu', function(e) {
    const latlng = e.latlng;
    const lat = latlng.lat.toFixed(6);
    const lng = latlng.lng.toFixed(6);

    L.popup()
    .setLatLng(latlng)
    .setContent(`Koordinat yang Anda klik:<br>${lat}, ${lng}`)
    .openOn(map);

    console.log(`Koordinat: ${lat}, ${lng}`);
  });

  // Aktifkan Geoman control (gambar/edit)
  map.pm.addControls({
    position: 'topleft',
    drawMarker: false,
    drawCircle: false,
    drawPolygon: false,
    drawPolyline: true,
    editMode: true,
    dragMode: false,
    removalMode: true,
  });

  // Tampilkan jarak total saat garis selesai digambar
  map.on('pm:create', e => {
    if (e.shape === 'Line') {
      const latlngs = e.layer.getLatLngs();
      let totalDistance = 0;

      for (let i = 1; i < latlngs.length; i++) {
        totalDistance += latlngs[i - 1].distanceTo(latlngs[i]);
      }

      const distanceInMeters = totalDistance.toFixed(2);
      e.layer.bindPopup(`Jarak total: ${distanceInMeters} meter`).openPopup();
    }
  });
</script>

@endsection



