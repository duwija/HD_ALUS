@extends('layout.main')
@section('title','PPPoE Offline Map')
@section('maps')
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.Default.css" />
@endsection
@section('content')
<style>
  #pppoe-offline-map {
    height: calc(100vh - 190px);
    min-height: 400px;
    border-radius: 12px;
    border: 1px solid var(--border);
    overflow: hidden;
  }
  .map-toolbar {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 10px 14px;
    margin-bottom: 8px;
    display: flex; align-items: center; flex-wrap: wrap; gap: 10px;
  }
  .map-stat {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;
  }
  .ms-offline { background:rgba(239,68,68,.12); color:#ef4444; border:1px solid rgba(239,68,68,.25); }
  #loadingOverlay {
    position: absolute; inset: 0; background: rgba(0,0,0,.45);
    display: flex; align-items: center; justify-content: center;
    border-radius: 12px; z-index: 1000; color: #fff; font-size: 16px; gap: 10px;
  }
  .leaflet-popup-content-wrapper {
    background: var(--bg-surface) !important;
    color: var(--text-primary) !important;
    border: 1px solid var(--border) !important;
    box-shadow: 0 4px 16px rgba(0,0,0,.3) !important;
  }
  .leaflet-popup-tip { background: var(--bg-surface) !important; }
  .popup-title { font-weight:700; font-size:13px; margin-bottom:4px; }
  .popup-row   { font-size:12px; color:var(--text-secondary); margin:2px 0; }
  .popup-badge {
    display:inline-block; padding:2px 8px; border-radius:20px; font-size:11px;
    font-weight:600; background:rgba(239,68,68,.15); color:#ef4444;
    border:1px solid rgba(239,68,68,.3); margin-top:4px;
  }
  .popup-badge-link {
    display:inline-block; padding:2px 8px; border-radius:20px; font-size:11px;
    font-weight:600; background:rgba(59,130,246,.15); color:#3b82f6;
    border:1px solid rgba(59,130,246,.3); margin-top:4px; margin-left:4px;
    text-decoration:none;
  }
  .popup-badge-link:hover { background:rgba(59,130,246,.28); color:#2563eb; text-decoration:none; }
  .map-legend {
    display:flex; align-items:center; gap:14px; flex-wrap:wrap;
    font-size:11px; color:var(--text-secondary); margin-bottom:6px;
  }
  .legend-dot  { width:12px;height:12px;border-radius:50%;border:2px solid #fff;display:inline-block;vertical-align:middle;margin-right:3px; }
  .legend-sq   { width:12px;height:12px;border-radius:3px;border:2px solid #fff;display:inline-block;vertical-align:middle;margin-right:3px; }
  .legend-line { width:24px;height:0;border-top:2px dashed;display:inline-block;vertical-align:middle;margin-right:3px; }
  @keyframes pppoe-pulse {
    0%   { box-shadow:0 0 0 0 rgba(239,68,68,.7),0 0 0 0 rgba(239,68,68,.4); }
    50%  { box-shadow:0 0 0 6px rgba(239,68,68,.0),0 0 0 10px rgba(239,68,68,.0); }
    100% { box-shadow:0 0 0 0 rgba(239,68,68,.7),0 0 0 0 rgba(239,68,68,.4); }
  }
  @keyframes pppoe-blink { 0%,100%{opacity:1} 50%{opacity:.35} }
  .pppoe-marker-dot {
    width:14px;height:14px;background:#ef4444;border:2px solid #fff;border-radius:50%;
    animation:pppoe-pulse 1.4s ease-out infinite,pppoe-blink 1.4s ease-in-out infinite;
  }
  .pppoe-odp-dot {
    width:14px;height:14px;background:#f59e0b;border:2px solid #fff;
    border-radius:3px;box-shadow:0 0 0 2px rgba(245,158,11,.4);
  }
  /* Legend animated flow preview */
  @keyframes legendFlow {
    from { stroke-dashoffset: 10; }
    to   { stroke-dashoffset: 0; }
  }
  .legend-flow-line { animation: legendFlow 1.2s linear infinite; }
  /* Map polyline flow animation keyframe */
  @keyframes dashFlow {
    from { stroke-dashoffset: 20; }
    to   { stroke-dashoffset: 0; }
  }
  path.odp-flow-line {
    animation: dashFlow 1.2s linear infinite;
    stroke-linecap: round;
  }
</style>

<div class="container-fluid">

  <div class="map-toolbar">
    <div style="flex:1">
      <span style="font-size:15px;font-weight:700;color:var(--text-primary)">
        <i class="fas fa-map-marked-alt mr-2" style="color:#ef4444"></i>PPPoE Offline Map
      </span>
      <span style="font-size:12px;color:var(--text-muted);margin-left:8px">Pelanggan offline + jaringan ODP</span>
    </div>

    <select id="routerFilter" class="form-control form-control-sm" style="width:auto;background:var(--input-bg);color:var(--text-primary);border-color:var(--input-border)">
      <option value="">Semua Router</option>
      @foreach($routers as $r)
      <option value="{{ $r->id }}">{{ $r->name }}</option>
      @endforeach
    </select>

    <button id="btnRefreshMap" class="btn btn-sm btn-outline-danger" style="border-radius:8px">
      <i class="fas fa-sync-alt mr-1"></i>Refresh
    </button>
    <a href="{{ route('pppoe.monitor') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px">
      <i class="fas fa-chart-line mr-1"></i>Monitor
    </a>

    <label style="display:inline-flex;align-items:center;gap:5px;font-size:12px;color:var(--text-secondary);cursor:pointer;margin:0">
      <input type="checkbox" id="showMapLayers" checked style="cursor:pointer">
      <i class="fas fa-layer-group" style="color:#8b5cf6"></i> Map Layers
    </label>

    <span id="statOffline" class="map-stat ms-offline" style="display:none;cursor:pointer" title="Klik untuk lihat daftar" onclick="openOfflineModal()">
      <i class="fas fa-circle" style="font-size:7px"></i><span id="countOffline">0</span> customer offline
    </span>

    <span id="statCountdown" class="map-stat" style="background:rgba(99,102,241,.1);color:#6366f1;border:1px solid rgba(99,102,241,.25)">
      <i class="fas fa-clock" style="font-size:10px"></i> <span id="countdownVal">3:00</span>
    </span>
  </div>

  <div class="map-legend">
    <span><span class="legend-dot" style="background:#ef4444"></span>Pelanggan Offline</span>
    <span><span class="legend-sq"  style="background:#f59e0b"></span>ODP / Dispoint</span>
    <span><span class="legend-sq"  style="background:#3b82f6"></span>Parent ODP</span>
    <span><span class="legend-line" style="border-color:#f59e0b"></span>Pelanggan &rarr; ODP</span>
    <span>
      <svg width="28" height="10" style="vertical-align:middle;margin-right:3px;overflow:visible">
        <path class="legend-flow-line" d="M0,5 L28,5"
          stroke="#60a5fa" stroke-width="2.5"
          stroke-dasharray="6,4" stroke-linecap="round" fill="none"/>
        <polygon points="24,2 28,5 24,8" fill="#60a5fa"/>
      </svg>Aliran Parent &rarr; Child ODP
    </span>
    <span><span class="legend-sq"  style="background:#8b5cf6"></span>Layer Tersimpan</span>
  </div>

  <div style="position:relative">
    <div id="pppoe-offline-map"></div>
    <div id="loadingOverlay">
      <i class="fas fa-spinner fa-spin"></i> Mengambil data dari router...
    </div>
  </div>

</div>

<!-- Offline Customer List Modal -->
<div class="modal fade" id="offlineListModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
    <div class="modal-content" style="background:var(--bg-surface);color:var(--text-primary);border:1px solid var(--border)">
      <div class="modal-header" style="border-color:var(--border);padding:12px 16px">
        <h5 class="modal-title" style="font-size:15px;font-weight:700">
          <i class="fas fa-exclamation-circle mr-2" style="color:#ef4444"></i>Daftar Customer Offline
        </h5>
        <button type="button" class="close" data-dismiss="modal" style="color:var(--text-primary);opacity:.7">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="offlineListBody" style="padding:0;max-height:70vh;overflow-y:auto">
        <!-- filled by JS -->
      </div>
      <div class="modal-footer" style="border-color:var(--border);padding:8px 16px">
        <span id="offlineModalCount" style="font-size:12px;color:var(--text-muted);flex:1"></span>
        <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

@endsection

@section('footer-scripts')
<style>
.offline-cluster div { background-color: rgba(239,68,68,.85); }
.offline-cluster { background-color: rgba(239,68,68,.3); }
</style>
<script>
// Poll until Leaflet (loaded async by main layout) is fully ready,
// then dynamically load MarkerCluster, then init map.
(function waitForLeaflet() {
  if (typeof L === 'undefined' || typeof L.map !== 'function') {
    return setTimeout(waitForLeaflet, 50);
  }
  if (typeof L.markerClusterGroup !== 'function') {
    var s = document.createElement('script');
    s.src = 'https://unpkg.com/leaflet.markercluster/dist/leaflet.markercluster.js';
    s.onload  = initPppoeMap;
    s.onerror = initPppoeMap; // still try without cluster
    document.head.appendChild(s);
    return;
  }
  initPppoeMap();
})();

function initPppoeMap() {
  var defaultLL = "{{ env('COORDINATE_CENTER', '-2.5,118') }}".split(',');

  var map = L.map('pppoe-offline-map', {
    zoomControl: true
  }).setView([parseFloat(defaultLL[0]), parseFloat(defaultLL[1])], 5);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors', maxZoom:19
  }).addTo(map);

  var redIcon = L.divIcon({
    className: '',
    html: '<div class="pppoe-marker-dot"></div>',
    iconSize:[14,14], iconAnchor:[7,7], popupAnchor:[0,-12]
  });
  var odpIcon = L.divIcon({
    className: '',
    html: '<div class="pppoe-odp-dot"></div>',
    iconSize:[14,14], iconAnchor:[7,7], popupAnchor:[0,-12]
  });
  var parentIcon = L.divIcon({
    className: '',
    html: '<div style="width:16px;height:16px;background:#3b82f6;border:2px solid #fff;border-radius:3px;box-shadow:0 0 0 2px rgba(59,130,246,.4)"></div>',
    iconSize:[16,16], iconAnchor:[8,8], popupAnchor:[0,-12]
  });

  // === Layer groups ===
  var offlineCluster = L.markerClusterGroup({
    maxClusterRadius: 80,
    spiderfyOnMaxZoom: true,
    showCoverageOnHover: false,
    zoomToBoundsOnClick: true,
    disableClusteringAtZoom: 14,
    animate: false,
    removeOutsideVisibleBounds: false,
    chunkedLoading: false,
    iconCreateFunction: function(cluster) {
      return L.divIcon({
        html: '<div><span>' + cluster.getChildCount() + '</span></div>',
        className: 'marker-cluster offline-cluster',
        iconSize: L.point(40, 40)
      });
    }
  });
  var odpGroup    = L.layerGroup().addTo(map);
  var parentGroup = L.layerGroup().addTo(map);
  var savedLayersGroup = L.layerGroup().addTo(map);
  offlineCluster.addTo(map);

  var customerData    = [];
  var customerMarkers = [];
  var odpLinksData    = [];
  var drawnPolylines  = []; // flat array, added directly to map (like distpoint/map)

  function showLoading(v) {
    document.getElementById('loadingOverlay').style.display = v ? 'flex' : 'none';
  }

  function drawPolylines() {
    // Remove previous polylines directly from map
    drawnPolylines.forEach(function(line) { map.removeLayer(line); });
    drawnPolylines.length = 0;

    // Customer → ODP lines (always draw)
    customerData.forEach(function(m) {
      if (m.odp_lat == null || m.odp_lng == null) return;
      var line = L.polyline([[m.lat, m.lng], [m.odp_lat, m.odp_lng]], {
        color:'#f59e0b', weight:2, dashArray:'6,5', opacity:.85
      }).addTo(map);
      drawnPolylines.push(line);
    });

    // ODP → Parent ODP lines — animated flow direction parent → child
    odpLinksData.forEach(function(link) {
      // coords: parent first → child last, so dashoffset animation flows parent→child
      var line = L.polyline([[link.parent_lat, link.parent_lng], [link.child_lat, link.child_lng]], {
        color:'#60a5fa', weight:2.5, dashArray:'12,8', opacity:.9,
        className: 'odp-flow-line'
      }).addTo(map);
      drawnPolylines.push(line);
    });
  }

  map.on('zoomend', drawPolylines);
  offlineCluster.on('animationend', drawPolylines);

  // === Saved Map Layers ===
  function parseCoords(raw) {
    if (Array.isArray(raw)) return raw;
    try { return JSON.parse(raw); } catch(e) { return []; }
  }

  function loadMapLayers() {
    $.getJSON('/map/layers', function(layers) {
      savedLayersGroup.clearLayers();
      layers.forEach(function(layer) {
        if (layer.type === 'group') return; // UI only, skip
        var coords = parseCoords(layer.coordinates);
        if (!coords || coords.length === 0) return;

        var leafletLayer;
        var popupHtml = '<div class="popup-title"><i class="fas fa-layer-group mr-1" style="color:#8b5cf6"></i>' + (layer.name || 'Layer') + '</div>'
          + (layer.description ? '<div class="popup-row">' + layer.description + '</div>' : '');

        if (layer.type === 'polyline') {
          leafletLayer = L.polyline(coords, {
            color:   layer.color   || '#3388ff',
            weight:  layer.weight  || 3,
            opacity: layer.opacity || 0.7
          }).bindPopup(popupHtml, { maxWidth: 260 });

        } else if (layer.type === 'polygon' || layer.type === 'rectangle') {
          leafletLayer = L.polygon(coords, {
            color:       layer.color   || '#3388ff',
            weight:      layer.weight  || 3,
            opacity:     layer.opacity || 0.7,
            fillOpacity: (layer.opacity || 0.7) * 0.25
          }).bindPopup(popupHtml, { maxWidth: 260 });

        } else if (layer.type === 'circle') {
          // coords[0] = [lat,lng], coords[1] = radius
          var center = coords[0];
          var radius = Array.isArray(coords[1]) ? coords[1][0] : (coords[1] || 100);
          leafletLayer = L.circle(center, {
            radius:      radius,
            color:       layer.color   || '#3388ff',
            weight:      layer.weight  || 3,
            opacity:     layer.opacity || 0.7,
            fillOpacity: (layer.opacity || 0.7) * 0.25
          }).bindPopup(popupHtml, { maxWidth: 260 });

        } else if (layer.type === 'marker') {
          var latlng = Array.isArray(coords[0]) ? coords[0] : coords;
          leafletLayer = L.marker(latlng, {
            icon: L.divIcon({
              className: '',
              html: '<div style="width:14px;height:14px;background:' + (layer.color||'#8b5cf6') + ';border:2px solid #fff;border-radius:50%;box-shadow:0 0 0 2px rgba(0,0,0,.2)"></div>',
              iconSize: [14,14], iconAnchor: [7,7], popupAnchor: [0,-10]
            })
          }).bindPopup(popupHtml, { maxWidth: 260 });
        }

        if (leafletLayer) {
          leafletLayer.addTo(savedLayersGroup);
        }
      });
    });
  }

  // Toggle saved layers visibility
  document.getElementById('showMapLayers').addEventListener('change', function() {
    if (this.checked) {
      map.addLayer(savedLayersGroup);
    } else {
      map.removeLayer(savedLayersGroup);
    }
  });

  function loadData() {
    showLoading(true);
    offlineCluster.clearLayers();
    odpGroup.clearLayers();
    parentGroup.clearLayers();
    drawnPolylines.forEach(function(line) { map.removeLayer(line); });
    drawnPolylines.length = 0;
    customerData    = [];
    customerMarkers = [];
    odpLinksData    = [];
    map.invalidateSize();

    var rid = document.getElementById('routerFilter').value;
    var url = '/pppoe-map/data' + (rid ? '?router_id=' + rid : '');

    $.getJSON(url, function(data) {
      showLoading(false);
      var pts     = data.markers   || [];
      var links   = data.odp_links || [];
      var odpInfo = data.odp_info  || {};  // keyed by odp id

      // Build helper: build ODP popup HTML from odp_info
      function buildOdpPopup(odpId, odpName, extraRow) {
        var info   = odpInfo[odpId] || {};
        var title  = info.name || odpName || 'ODP';
        var count  = (info.customer_count !== undefined) ? info.customer_count : '-';
        var desc   = info.description && info.description !== '-' ? '<div class="popup-row"><i class="fas fa-info-circle mr-1" style="width:14px"></i>' + info.description + '</div>' : '';
        var btnLink = odpId ? '<a href="/distpoint/' + odpId + '" target="_blank" class="popup-badge-link" style="background:rgba(245,158,11,.15);color:#f59e0b;border-color:rgba(245,158,11,.3)"><i class="fas fa-external-link-alt mr-1"></i>Lihat ODP</a>' : '';
        return '<div class="popup-title"><i class="fas fa-map-marker-alt mr-1" style="color:#f59e0b"></i>' + title + '</div>'
          + '<div class="popup-row"><i class="fas fa-users mr-1" style="width:14px"></i>Pelanggan terhubung: <strong>' + count + '</strong></div>'
          + desc
          + (extraRow || '')
          + '<div style="margin-top:5px">' + btnLink + '</div>';
      }

      document.getElementById('statOffline').style.display = pts.length ? 'inline-flex' : 'none';
      document.getElementById('countOffline').textContent  = pts.length;

      customerData = pts;
      odpLinksData = links;

      var bounds       = [];
      var addedOdps    = {};
      var addedParents = {};

      pts.forEach(function(m, i) {
        var ll = [m.lat, m.lng];
        bounds.push(ll);

        var popup = '<div class="popup-title"><i class="fas fa-user mr-1"></i>' + m.name + '</div>'
          + '<div class="popup-row"><i class="fas fa-id-card mr-1" style="width:14px"></i>' + m.customer_id + '</div>'
          + '<div class="popup-row"><i class="fas fa-wifi mr-1" style="width:14px"></i>PPPoE: <strong>' + m.pppoe + '</strong></div>'
          + (m.phone   ? '<div class="popup-row"><i class="fas fa-phone mr-1" style="width:14px"></i>' + m.phone + '</div>' : '')
          + (m.address ? '<div class="popup-row"><i class="fas fa-map-pin mr-1" style="width:14px"></i>' + m.address + '</div>' : '')
          + '<div class="popup-row"><i class="fas fa-server mr-1" style="width:14px"></i>' + m.router + '</div>'
          + (m.last_offline ? '<div class="popup-row" style="color:#f59e0b"><i class="fas fa-clock mr-1" style="width:14px"></i>Offline sejak: <b>' + m.last_offline + '</b></div>' : '')
          + '<span class="popup-badge"><i class="fas fa-exclamation-circle mr-1"></i>Offline</span>'
          + (m.id ? '<a href="/customer/' + m.id + '" target="_blank" class="popup-badge-link"><i class="fas fa-external-link-alt mr-1"></i>Lihat Pelanggan</a>' : '');

        var marker = L.marker(ll, { icon: redIcon }).bindPopup(popup, { maxWidth: 260 });
        offlineCluster.addLayer(marker);
        customerMarkers[i] = marker;

        if (m.odp_lat != null && m.odp_lng != null && !addedOdps[m.odp_name]) {
          addedOdps[m.odp_name] = true;
          bounds.push([m.odp_lat, m.odp_lng]);
          L.marker([m.odp_lat, m.odp_lng], { icon: odpIcon })
            .bindPopup(buildOdpPopup(m.odp_id, m.odp_name), { maxWidth: 240 })
            .addTo(odpGroup);
        }
      });

      links.forEach(function(link) {
        var childll  = [link.child_lat,  link.child_lng];
        var parentll = [link.parent_lat, link.parent_lng];
        bounds.push(childll);
        bounds.push(parentll);

        if (!addedOdps[link.child_name]) {
          addedOdps[link.child_name] = true;
          L.marker(childll, { icon: odpIcon })
            .bindPopup(buildOdpPopup(link.child_id, link.child_name,
              '<div class="popup-row" style="font-size:11px"><i class="fas fa-sitemap mr-1" style="width:14px"></i>Child ODP &rarr; <b>' + link.parent_name + '</b></div>'), { maxWidth: 240 })
            .addTo(odpGroup);
        }
        if (!addedParents[link.parent_name]) {
          addedParents[link.parent_name] = true;
          L.marker(parentll, { icon: parentIcon })
            .bindPopup(buildOdpPopup(link.parent_id, link.parent_name,
              '<div class="popup-row" style="font-size:11px"><i class="fas fa-sitemap mr-1" style="width:14px"></i>Parent ODP</div>'), { maxWidth: 240 })
            .addTo(parentGroup);
        }
      });

      if (bounds.length === 1)    map.setView(bounds[0], 15);
      else if (bounds.length > 1) map.fitBounds(bounds, { padding: [40, 40], maxZoom: 15 });

      // Redraw polylines after fitBounds animation settles
      setTimeout(drawPolylines, 500);

    }).fail(function(xhr) {
      showLoading(false);
      console.error('[PPPoE Map] Fetch failed:', xhr.status, xhr.responseText);
      alert('Gagal mengambil data. Status: ' + xhr.status);
    });
  }

  // === Offline modal ===
  window.openOfflineModal = function() {
    var rows = '';
    if (!customerData.length) {
      rows = '<p class="text-center" style="padding:20px;color:var(--text-muted)">Tidak ada customer offline.</p>';
    } else {
      rows += '<table class="table table-sm" style="margin:0;font-size:12px">';
      rows += '<thead style="position:sticky;top:0;background:var(--bg-surface);z-index:1">'
            + '<tr>'
            + '<th style="width:32px">#</th>'
            + '<th>Nama</th>'
            + '<th>PPPoE</th>'
            + '<th>Router</th>'
            + '<th>Offline Sejak</th>'
            + '<th></th>'
            + '</tr></thead><tbody>';
      customerData.forEach(function(m, i) {
        rows += '<tr>'
          + '<td style="color:var(--text-muted)">' + (i+1) + '</td>'
          + '<td><strong>' + (m.name||'-') + '</strong><br><span style="color:var(--text-muted);font-size:11px">' + (m.customer_id||'') + '</span></td>'
          + '<td><code style="font-size:11px">' + (m.pppoe||'-') + '</code></td>'
          + '<td style="font-size:11px">' + (m.router||'-') + '</td>'
          + '<td style="color:#f59e0b;font-size:11px">' + (m.last_offline||'-') + '</td>'
          + '<td>' + (m.id ? '<a href="/customer/'+m.id+'" target="_blank" class="btn btn-xs btn-outline-primary" style="font-size:10px;padding:1px 7px;border-radius:20px"><i class="fas fa-external-link-alt"></i></a>' : '') + '</td>'
          + '</tr>';
      });
      rows += '</tbody></table>';
    }
    document.getElementById('offlineListBody').innerHTML  = rows;
    document.getElementById('offlineModalCount').textContent = customerData.length + ' customer offline';
    $('#offlineListModal').modal('show');
  };

  document.getElementById('btnRefreshMap').addEventListener('click', function() {
    resetCountdown();
    loadData();
  });
  document.getElementById('routerFilter').addEventListener('change', function() {
    resetCountdown();
    loadData();
  });

  // === Auto-reload every 3 minutes ===
  var RELOAD_INTERVAL = 180;
  var countdownSec    = RELOAD_INTERVAL;
  var countdownTimer  = null;
  var reloadTimer     = null;

  function formatCountdown(s) {
    return Math.floor(s / 60) + ':' + (s % 60 < 10 ? '0' : '') + (s % 60);
  }

  function resetCountdown() {
    clearInterval(countdownTimer);
    clearTimeout(reloadTimer);
    countdownSec = RELOAD_INTERVAL;
    document.getElementById('countdownVal').textContent = formatCountdown(countdownSec);
    startCountdown();
  }

  function startCountdown() {
    countdownTimer = setInterval(function() {
      countdownSec--;
      document.getElementById('countdownVal').textContent = formatCountdown(countdownSec);
      if (countdownSec <= 0) {
        clearInterval(countdownTimer);
      }
    }, 1000);

    reloadTimer = setTimeout(function() {
      loadData();
      loadMapLayers();
      resetCountdown();
    }, RELOAD_INTERVAL * 1000);
  }

  // Invalidate size once layout has settled, then load data
  setTimeout(function() {
    map.invalidateSize();
    loadData();
    loadMapLayers();
    startCountdown();
  }, 400);
} // end initPppoeMap
</script>
@endsection
