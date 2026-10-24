{{--
  Peta titik layanan / POP (Leaflet + OpenStreetMap). Dipakai di admin/pks/show dan isp/pks.
  Parameter: $mapId, $markers (array dari PksServicePoint::toMapMarker()), $pickable (klik peta
  mengisi input .point-koordinat di form tambah / modal ubah — hanya halaman admin).
--}}
@once
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <style>.pks-map { height: 360px; border-radius: 6px; z-index: 0; }</style>
@endonce

<div id="{{ $mapId }}" class="pks-map mb-2"></div>
@unless (count($markers))
  <small class="text-muted d-block mb-2"><i class="fas fa-info-circle"></i> Belum ada titik dengan koordinat.{{ !empty($pickable) ? ' Klik peta untuk mengisi koordinat saat menambah/mengubah titik.' : '' }}</small>
@endunless

<script>
(function () {
  var markers = {!! json_encode(array_values($markers), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!};
  var pickable = {{ !empty($pickable) ? 'true' : 'false' }};
  var el = document.getElementById('{{ $mapId }}');
  var map = L.map(el, { scrollWheelZoom: false });
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19, attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map);

  var esc = function (s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; };
  var colors = { pop: '#0d47a1', titik_layanan: '#17a2b8' };
  var byId = {};
  var bounds = [];
  markers.forEach(function (m) {
    var mk = L.circleMarker([m.lat, m.lng], {
      radius: m.jenis === 'pop' ? 10 : 8, color: '#fff', weight: 2,
      fillColor: colors[m.jenis] || '#6c757d', fillOpacity: 0.95
    }).addTo(map).bindPopup(
      '<strong>' + esc(m.nama) + '</strong><br>' + esc(m.jenis_label) + ' &middot; ' +
      Number(m.kapasitas).toLocaleString('id-ID') + ' Mbps' +
      (m.lokasi ? '<br><small>' + esc(m.lokasi) + '</small>' : '') +
      '<br><a href="https://www.google.com/maps/search/?api=1&query=' + m.lat + ',' + m.lng + '" target="_blank">Buka di Google Maps</a>'
    );
    byId[m.id] = mk;
    bounds.push([m.lat, m.lng]);
  });

  if (bounds.length > 1) map.fitBounds(bounds, { padding: [30, 30] });
  else if (bounds.length === 1) map.setView(bounds[0], 15);
  else map.setView([-8.45, 115.2], 9); // Bali

  // Legenda
  var legend = L.control({ position: 'bottomleft' });
  legend.onAdd = function () {
    var d = L.DomUtil.create('div', 'bg-white p-1 rounded small shadow-sm');
    d.innerHTML = '<span style="color:' + colors.pop + '">&#9679;</span> POP &nbsp; <span style="color:' + colors.titik_layanan + '">&#9679;</span> Titik Layanan';
    return d;
  };
  legend.addTo(map);

  // Peta di dalam card yang tersembunyi/di-collapse perlu dihitung ulang ukurannya saat tampil.
  if (window.ResizeObserver) new ResizeObserver(function () { map.invalidateSize(); }).observe(el);

  window.pksMaps = window.pksMaps || {};
  window.pksMaps['{{ $mapId }}'] = {
    focus: function (id) {
      if (!byId[id]) return;
      el.scrollIntoView({ behavior: 'smooth', block: 'center' });
      map.setView(byId[id].getLatLng(), 16);
      byId[id].openPopup();
    }
  };

  if (pickable) {
    var pick = null;
    map.on('click', function (e) {
      var value = e.latlng.lat.toFixed(6) + ',' + e.latlng.lng.toFixed(6);
      var modal = document.getElementById('point-edit-modal');
      var input = modal && modal.classList.contains('show')
        ? modal.querySelector('.point-koordinat')
        : document.querySelector('#point-add .point-koordinat');
      if (!input) return;
      if (!(modal && modal.classList.contains('show')) && window.jQuery) jQuery('#point-add').collapse('show');
      input.value = value;
      if (pick) map.removeLayer(pick);
      pick = L.marker(e.latlng).addTo(map).bindPopup('Koordinat dipilih: ' + value).openPopup();
    });
    el.style.cursor = 'crosshair';
  }
})();
</script>
