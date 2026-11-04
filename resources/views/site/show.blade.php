@extends('layout.main')
@section('title', 'Site')

@section('content')
<style>
  #site-map {
    height: calc(100vh - 230px);
    min-height: 420px;
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
  .ms-odp  { background:rgba(245,158,11,.12); color:#f59e0b; border:1px solid rgba(245,158,11,.25); }
  .ms-cust { background:rgba(16,185,129,.1);  color:#10b981; border:1px solid rgba(16,185,129,.25); }
  .ms-warn { background:rgba(107,114,128,.15); color:#9ca3af; border:1px solid rgba(107,114,128,.3); }
  #siteMapViewport:fullscreen,
  #siteMapViewport:-webkit-full-screen { background: #0b1220; padding: 10px; }
  #siteMapViewport:fullscreen #site-map,
  #siteMapViewport:-webkit-full-screen #site-map {
    height: calc(100vh - 20px); min-height: calc(100vh - 20px); border-radius: 0;
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
  .popup-badge-link {
    display:inline-block; padding:2px 8px; border-radius:20px; font-size:11px;
    font-weight:600; background:rgba(59,130,246,.15); color:#3b82f6;
    border:1px solid rgba(59,130,246,.3); margin-top:4px; text-decoration:none;
  }
  .popup-badge-link:hover { background:rgba(59,130,246,.28); color:#2563eb; text-decoration:none; }
  .map-legend {
    display:flex; align-items:center; gap:14px; flex-wrap:wrap;
    font-size:11px; color:var(--text-secondary); margin-bottom:6px;
  }
  .legend-sq { width:12px;height:12px;border-radius:3px;border:2px solid #fff;display:inline-block;vertical-align:middle;margin-right:3px; }
  .site-odp-dot {
    width:14px;height:14px;background:#f59e0b;border:2px solid #fff;
    border-radius:3px;box-shadow:0 0 0 2px rgba(245,158,11,.4);
  }
  .site-parent-dot {
    width:16px;height:16px;background:#3b82f6;border:2px solid #fff;
    border-radius:3px;box-shadow:0 0 0 2px rgba(59,130,246,.4);
  }
  .site-pop-dot {
    width:24px;height:24px;background:#8b5cf6;border:3px solid #fff;border-radius:6px;
    box-shadow:0 0 0 3px rgba(139,92,246,.45);
    display:flex;align-items:center;justify-content:center;color:#fff;font-size:11px;
  }
  @keyframes legendFlow { from { stroke-dashoffset: 10; } to { stroke-dashoffset: 0; } }
  .legend-flow-line { animation: legendFlow 1.2s linear infinite; }
  @keyframes dashFlow { from { stroke-dashoffset: 20; } to { stroke-dashoffset: 0; } }
  path.odp-flow-line { animation: dashFlow 1.2s linear infinite; stroke-linecap: round; }
</style>

<div class="container-fluid">
  <div class="map-toolbar">
    <div style="flex:1;min-width:220px">
      <span style="font-size:15px;font-weight:700;color:var(--text-primary)">
        <i class="fas fa-broadcast-tower mr-2" style="color:#8b5cf6"></i>{{ $site->name }}
      </span>
      <span style="font-size:12px;color:var(--text-muted);margin-left:8px">
        {{ $site->location ?: '-' }}@if($site->description) &middot; {{ $site->description }}@endif
      </span>
    </div>

    <span class="map-stat ms-odp" title="ODP yang tampil di peta">
      <i class="fas fa-project-diagram" style="font-size:10px"></i> {{ $stats['odp'] }} ODP
    </span>
    <span class="map-stat ms-cust" title="Total pelanggan di ODP site ini">
      <i class="fas fa-users" style="font-size:10px"></i> {{ $stats['customers'] }} pelanggan
    </span>
    @if($stats['odp_nocoord'] > 0)
    <span class="map-stat ms-warn" title="ODP tanpa koordinat tidak tampil di peta">
      <i class="fas fa-map-marker-alt" style="font-size:10px"></i> {{ $stats['odp_nocoord'] }} ODP tanpa koordinat
    </span>
    @endif

    @if($site->coordinate)
    <a href="https://www.google.com/maps/place/{{ $site->coordinate }}" target="_blank" class="btn btn-sm btn-outline-info" style="border-radius:8px">
      <i class="fas fa-map mr-1"></i>Google Maps
    </a>
    @endif
    <button id="btnSiteFullscreen" class="btn btn-sm btn-outline-secondary" style="border-radius:8px" title="Mode Full Screen">
      <i class="fas fa-expand-arrows-alt mr-1"></i>Full Screen
    </button>
    <a href="/site/{{ $site->id }}/edit" class="btn btn-sm btn-outline-primary" style="border-radius:8px">
      <i class="fas fa-edit mr-1"></i>Edit
    </a>
    <form action="/site/{{ $site->id }}" method="POST" class="d-inline site-delete m-0">
      @method('delete')
      @csrf
      <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius:8px">
        <i class="fas fa-trash mr-1"></i>Delete
      </button>
    </form>
  </div>

  <div class="map-legend">
    <span><span class="legend-sq" style="background:#8b5cf6"></span>POP / Site</span>
    <span><span class="legend-sq" style="background:#3b82f6"></span>Parent ODP</span>
    <span><span class="legend-sq" style="background:#f59e0b"></span>ODP</span>
    <span>
      <svg width="28" height="10" style="vertical-align:middle;margin-right:3px;overflow:visible">
        <path class="legend-flow-line" d="M0,5 L28,5" stroke="#60a5fa" stroke-width="2.5"
          stroke-dasharray="6,4" stroke-linecap="round" fill="none"/>
        <polygon points="24,2 28,5 24,8" fill="#60a5fa"/>
      </svg>Aliran Parent &rarr; Child ODP
    </span>
  </div>

  <div id="siteMapViewport" style="position:relative">
    <div id="site-map"></div>
  </div>
</div>
@endsection

@section('footer-scripts')
<script>
(function () {
  var center    = @json($center);
  var locations = @json($locations);

  function parseCoord(str) {
    if (!str) return null;
    var p = String(str).split(',').map(function (v) { return parseFloat(v); });
    return (p.length >= 2 && !isNaN(p[0]) && !isNaN(p[1])) ? [p[0], p[1]] : null;
  }
  function esc(v) { return $('<div>').text(v == null ? '' : String(v)).html(); }

  // Leaflet di layout dimuat async — tunggu sampai siap
  (function waitForLeaflet(tries) {
    if (typeof L === 'undefined' || typeof L.map !== 'function') {
      if (tries > 200) { document.getElementById('site-map').innerHTML = '<div class="p-3 text-muted">Peta gagal dimuat.</div>'; return; }
      return setTimeout(function () { waitForLeaflet(tries + 1); }, 50);
    }
    initSiteMap();
  })(0);

  function initSiteMap() {
    var start = parseCoord(center.coordinate) || [-8.5, 115.2];
    var map = L.map('site-map', { zoomControl: true }).setView(start, center.zoom || 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors', maxZoom: 19
    }).addTo(map);

    var odpIcon = L.divIcon({ className: '', html: '<div class="site-odp-dot"></div>', iconSize: [14, 14], iconAnchor: [7, 7], popupAnchor: [0, -10] });
    var parentIcon = L.divIcon({ className: '', html: '<div class="site-parent-dot"></div>', iconSize: [16, 16], iconAnchor: [8, 8], popupAnchor: [0, -10] });
    var popIcon = L.divIcon({ className: '', html: '<div class="site-pop-dot"><i class="fas fa-broadcast-tower"></i></div>', iconSize: [24, 24], iconAnchor: [12, 12], popupAnchor: [0, -14] });

    var lineGroup = L.layerGroup().addTo(map);
    var odpGroup  = L.layerGroup().addTo(map);
    var bounds = [];

    locations.forEach(function (loc) {
      if (loc.lat == null || loc.lng == null) return;
      var ll = [loc.lat, loc.lng];
      bounds.push(ll);

      if (loc.type === 'pop') {
        var popPopup = '<div class="popup-title"><i class="fas fa-broadcast-tower mr-1"></i>' + esc(loc.name) + '</div>'
          + (loc.location ? '<div class="popup-row"><i class="fas fa-map-pin mr-1" style="width:14px"></i>' + esc(loc.location) + '</div>' : '')
          + (loc.description ? '<div class="popup-row"><i class="fas fa-info-circle mr-1" style="width:14px"></i>' + esc(loc.description) + '</div>' : '');
        L.marker(ll, { icon: popIcon, zIndexOffset: 1000 }).bindPopup(popPopup, { maxWidth: 260 }).addTo(odpGroup);
        return;
      }

      // Garis aliran parent → child (beranimasi)
      if (loc.parent) {
        L.polyline([loc.parent, ll], {
          color: '#60a5fa', weight: 2.5, dashArray: '12,8', opacity: .9, className: 'odp-flow-line'
        }).addTo(lineGroup);
      }

      var popup = '<div class="popup-title"><i class="fas fa-project-diagram mr-1"></i>' + esc(loc.name) + '</div>'
        + '<div class="popup-row"><i class="fas fa-plug mr-1" style="width:14px"></i>Port: <strong>' + esc(loc.port) + '</strong></div>'
        + '<div class="popup-row"><i class="fas fa-users mr-1" style="width:14px"></i>Pelanggan: <strong>' + esc(loc.customers) + '</strong></div>'
        + (loc.parent_name ? '<div class="popup-row"><i class="fas fa-level-up-alt mr-1" style="width:14px"></i>Parent: ' + esc(loc.parent_name) + '</div>' : '')
        + (loc.description ? '<div class="popup-row"><i class="fas fa-info-circle mr-1" style="width:14px"></i>' + esc(loc.description) + '</div>' : '')
        + '<a href="/distpoint/' + loc.id + '" target="_blank" class="popup-badge-link"><i class="fas fa-external-link-alt mr-1"></i>Lihat ODP</a>';

      L.marker(ll, { icon: loc.is_parent ? parentIcon : odpIcon, title: loc.name })
        .bindPopup(popup, { maxWidth: 260 })
        .addTo(odpGroup);
    });

    if (bounds.length > 1) {
      map.fitBounds(bounds, { padding: [30, 30] });
    } else if (bounds.length === 1) {
      map.setView(bounds[0], 16);
    }

    // Pencarian ODP (plugin leaflet-search dari layout, bila sudah termuat)
    if (L.Control && L.Control.Search) {
      map.addControl(new L.Control.Search({
        layer: odpGroup, propertyName: 'title', initial: false, marker: false,
        moveToLocation: function (latlng) { map.setView(latlng, 18); }
      }));
    }

    // Full screen
    var viewport = document.getElementById('siteMapViewport');
    var btnFs = document.getElementById('btnSiteFullscreen');
    function isFs() { return document.fullscreenElement === viewport || document.webkitFullscreenElement === viewport; }
    function updateFsButton() {
      btnFs.innerHTML = isFs()
        ? '<i class="fas fa-compress-arrows-alt mr-1"></i>Exit Full Screen'
        : '<i class="fas fa-expand-arrows-alt mr-1"></i>Full Screen';
      setTimeout(function () { map.invalidateSize(); }, 200);
    }
    btnFs.addEventListener('click', function () {
      if (isFs()) {
        (document.exitFullscreen || document.webkitExitFullscreen).call(document);
      } else {
        (viewport.requestFullscreen || viewport.webkitRequestFullscreen).call(viewport);
      }
    });
    document.addEventListener('fullscreenchange', updateFsButton);
    document.addEventListener('webkitfullscreenchange', updateFsButton);
  }
})();
</script>
@endsection
