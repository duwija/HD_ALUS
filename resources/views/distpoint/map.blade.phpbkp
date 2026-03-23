@extends('layout.main')
@section('title', 'ODP Map')

@section('content')
<div class="card">
  <div id="map" style="height: 1000px;"></div>
</div>
@endsection

@section('footer-scripts')
<!-- Leaflet & MarkerCluster -->
<!-- Leaflet-Geoman -->

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

<script>
  const defaultLatLng = "{{ env('COORDINATE_CENTER', '-6.200000,106.816666') }}".split(',');
  const lat = parseFloat(defaultLatLng[0]);
  const lng = parseFloat(defaultLatLng[1]);

  const map = L.map('map').setView([lat, lng], 13);
  



// Peta
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map);

    // Menangani klik pada peta untuk mendapatkan koordinat
  map.on('contextmenu', function(e) {
    const latlng = e.latlng; // Mendapatkan koordinat lat, lng
    const lat = latlng.lat.toFixed(6); // Membulatkan menjadi 6 digit
    const lng = latlng.lng.toFixed(6);
      // Menampilkan koordinat dalam popup
    L.popup()
    .setLatLng(latlng)
    .setContent(`Koordinat yang Anda klik:<br> ${lat}, ${lng}`)
    .openOn(map);

    // Atau menampilkan dalam console
    console.log(`Koordinat yang Anda klik: Lat: ${lat}, Lng: ${lng}`);
  });


// Aktifkan kontrol Geoman (pengukuran & penggambaran)
  map.pm.addControls({
    position: 'topleft',
    drawCircle: false,
    drawMarker: false,
  drawPolyline: true, // aktifkan menggambar garis
  editMode: true,
  dragMode: false,
  cutPolygon: false,
  removalMode: true
});

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


// === Layer Checkbox Control ===
  const layerControl = L.control({ position: 'topright' });
  layerControl.onAdd = function (map) {
    const div = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
    div.style.cssText = `
    background: white;
    padding: 8px;
    border-radius: 8px;

    z-index: 1000;
    box-shadow: 0 2px 6px rgba(0,0,0,0.3);
    `;
    div.innerHTML = `
    <div class="form-check mb-2">
    <input class="form-check-input" type="checkbox" id="show-odp" checked />
    <label class="form-check-label" for="show-odp">Tampilkan ODP</label>
    </div>
    <div class="form-check">
    <input class="form-check-input" type="checkbox" id="show-tickets" checked />
    <label class="form-check-label" for="show-tickets">Tampilkan Tiket</label>
    </div>
    `;
    L.DomEvent.disableClickPropagation(div);
    return div;
  };
  layerControl.addTo(map);

// === Marker Groups ===
  const markers = L.markerClusterGroup();
  const odpMarkers = {};
  const odpPolylines = [];
  let odpData = {};
  let ticketMarkers = [];

// Gambar ulang semua polyline berdasarkan visibilitas child saja
  function drawPolylines() {
  // Hapus semua garis polyline sebelumnya
    odpPolylines.forEach(line => map.removeLayer(line));
    odpPolylines.length = 0;

    Object.values(odpData).forEach(odp => {
      if (
        odp.parrent !== null &&
        odp.parrent !== 0 &&
        odp.parrent !== 1 &&
        odp.parent_lat &&
        odp.parent_lng
        ) {
        const childMarker = odpMarkers[odp.id];

      // Cek apakah child terlihat, tidak peduli parent
      const childVisible = markers.getVisibleParent(childMarker) === childMarker;

      if (childVisible) {
        const childCoord = [odp.lat, odp.lng];
        const parentCoord = [odp.parent_lat, odp.parent_lng];

        const line = L.polyline([childCoord, parentCoord], {
          color: 'blue',
          weight: 2,
          opacity: 0.6
        }).addTo(map);

        odpPolylines.push(line);
      }
    }
  });
  }




// === Data ODP ===
  fetch('/distpoint/data')
  .then(res => res.json())
  .then(data => {
    odpData = data;
    Object.values(data).forEach(odp => {
      const marker = L.marker([odp.lat, odp.lng], { title: odp.name })

      .bindPopup(`
        <a href="${odp.button_link}"  target="_blank">
        <strong>${odp.name}</strong>
        </a><br>
        Port: ${odp.Capacity}<br>
        Parent: ${odp.parent_name ?? 'Tidak ditemukan'}<br>
        ${odp.description}<br>
        `);
      markers.addLayer(marker);
      odpMarkers[odp.id] = marker;
    });

    map.addLayer(markers);
    drawPolylines();
    // === Leaflet Search Control ===

        // Tambahkan kontrol search lokal untuk ODP
    const searchControl = new L.Control.Search({
      layer: markers,
      propertyName: 'title',
      zoom: 16,
      initial: false,
      hideMarkerOnCollapse: true,
      position: 'topright'
    });

    map.addControl(searchControl);
 // Tambahkan label di atas input search ODP
    setTimeout(() => {
      const container = document.querySelector('.leaflet-control-search');
      if (container) {
        const label = document.createElement('a');
        label.innerText = 'ODP';
        label.style.fontSize = '12px';
        label.style.margin = '4px';
        label.style.color = '#333';
        container.insertBefore(label, container.firstChild);
      }
    }, 100);
  });

  map.on('zoomend', drawPolylines);
  markers.on('animationend', drawPolylines);

// === Data Tiket ===
  fetch('/ticket/datamap')
  .then(res => res.json())
  .then(tickets => {
    tickets.forEach(ticket => {
      const color = getStatusColor(ticket.status);
      const icon = L.divIcon({
        className: 'ticket-icon',
        html: `<i class="fas fa-flag" style="color: ${color}; font-size: 24px;"></i>`,
        iconSize: [30, 30],
        iconAnchor: [15, 15]
      });

      const marker = L.marker([ticket.lat, ticket.lng], { icon: icon })
      .bindPopup(`
        <strong>
        <a href="/ticket/${ticket.id}" target="_blank">Tiket #${ticket.id} | ${ticket.description}</a>
        </strong><br>
        Assign to: ${ticket.assign_to}<br>
        Customer: ${ticket.customer_name}<br>
        Status: <a class="badge" style="color: ${color};">${ticket.status}</a><br>

        `);

      ticketMarkers.push(marker);
    });

  updateMapLayers(); // Tampilkan sesuai checkbox saat data selesai dimuat
});

// Fungsi untuk menampilkan atau menyembunyikan ODP dan Tiket berdasarkan checkbox
  function updateMapLayers() {
  // Cek apakah checkbox ODP dicentang
    if (document.getElementById('show-odp').checked) {
    map.addLayer(markers); // Menambahkan marker ODP
    drawPolylines(); // Menambahkan polyline
  } else {
    map.removeLayer(markers); // Menghapus marker ODP
    odpPolylines.forEach(line => map.removeLayer(line)); // Menghapus polyline
  }

  // Cek apakah checkbox Tiket dicentang
  ticketMarkers.forEach(marker => {
    if (document.getElementById('show-tickets').checked) {
      marker.addTo(map); // Menambahkan marker tiket
    } else {
      map.removeLayer(marker); // Menghapus marker tiket
    }
  });
}

// === Checkbox Event Listener ===
document.getElementById('show-odp').addEventListener('change', updateMapLayers);
document.getElementById('show-tickets').addEventListener('change', updateMapLayers);

// === Geocoder (Search) ===
let searchMarker = null;

L.Control.geocoder({
  defaultMarkGeocode: false,
  position: 'topleft' // dipindah ke kiri atas agar tidak tertutup
})

.on('markgeocode', function (e) {
  const latlng = e.geocode.center;

  // Hapus marker sebelumnya jika ada
  if (searchMarker) {
    map.removeLayer(searchMarker);
  }

  // Tambahkan marker baru
  searchMarker = L.marker(latlng)
  .addTo(map)
  .bindPopup(`Hasil Pencarian:<br><strong>${e.geocode.name}</strong>`)
  .openPopup();

  map.setView(latlng, 16); // Atur zoom dan pindah ke lokasi
})

.addTo(map);

// === Tombol Lokasi Saya ===
const locateControl = L.control({ position: 'topleft' });

locateControl.onAdd = function (map) {
  const div = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
  div.innerHTML = `
  <button title="Lokasi Saya" class="leaflet-control-locate" style="background-color: transparent; border: none; cursor: pointer;">
  <img src="https://cdn-icons-png.flaticon.com/512/684/684908.png" alt="Lokasi Saya" style="width: 24px; height: 24px;" />
  </button>
  `;
  div.onclick = function (e) {
    e.preventDefault();
    map.locate({ setView: true, maxZoom: 16 });
  };
  return div;
};

locateControl.addTo(map);

map.on('locationfound', function (e) {
  L.circleMarker(e.latlng, {
    radius: 8,
    color: 'blue',
    fillColor: '#30f',
    fillOpacity: 0.5
  }).addTo(map)
  .bindPopup("Lokasi Anda Sekarang")
  .openPopup();
});

map.on('locationerror', function (e) {
  alert("Tidak bisa mendapatkan lokasi Anda. Pastikan izin lokasi diaktifkan.");
});

// === Fungsi Warna Tiket ===
function getStatusColor(status) {
  switch (status.toLowerCase()) {
  case 'open': return 'red';
  case 'close': return 'grey';
  case 'pending': return 'yellow';
  case 'solve': return 'green';
  case 'inprogress': return 'blue';
  default: return 'gray';
  }
}
</script>
@endsection
