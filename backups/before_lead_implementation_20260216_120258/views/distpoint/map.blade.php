@extends('layout.main')
@section('title', 'ODP Map')

@section('content')
<script>
window.csrfToken = '{{ csrf_token() }}';
</script>
<style>
  /* Container Flex Layout */
  .map-layout-container {
    display: flex;
    gap: 0;
    height: calc(100vh - 200px);
    min-height: 600px;
  }
  
  /* Layer Sidebar Panel */
  #layerSidebar {
    width: 320px;
    background: white;
    border-radius: 8px 0 0 8px;
    box-shadow: 2px 0 10px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
    transition: all 0.3s ease;
    overflow: hidden;
  }
  
  #layerSidebar.collapsed {
    width: 50px;
  }
  
  #layerSidebar .sidebar-header {
    background: linear-gradient(135deg, #a3301c 0%, #8a2817 100%);
    color: white;
    padding: 15px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    user-select: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }
  
  #layerSidebar .sidebar-header:hover {
    background: linear-gradient(135deg, #8a2817 0%, #a3301c 100%);
  }
  
  #layerSidebar .sidebar-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 600;
    font-size: 15px;
    transition: opacity 0.3s;
  }
  
  #layerSidebar.collapsed .sidebar-title span {
    opacity: 0;
    width: 0;
    overflow: hidden;
  }
  
  #layerSidebar .sidebar-body {
    overflow-y: auto;
    padding: 10px;
    flex: 1;
    background: #f9f9f9;
  }
  
  #layerSidebar.collapsed .sidebar-body {
    display: none;
  }
  
  /* Map Container */
  .map-container {
    flex: 1;
    position: relative;
    border-radius: 0 8px 8px 0;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  }
  
  /* Layer Items */
  .layer-item {
    background: white;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 10px;
    cursor: move;
    transition: all 0.2s;
    border: 1px solid #e0e0e0;
    user-select: none;
    opacity: 1;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  }
  
  /* Group Header Styling - More prominent */
  .layer-group {
    margin-bottom: 15px;
  }
  
  .layer-group-header {
    background: linear-gradient(135deg, #d32f2f 0%, #b71c1c 100%);
    border: none;
    border-radius: 8px;
    padding: 12px 15px;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 3px 12px rgba(211, 47, 47, 0.25);
    margin-bottom: 8px;
  }
  
  .layer-group-header:hover {
    box-shadow: 0 4px 16px rgba(211, 47, 47, 0.35);
    transform: translateY(-1px);
  }
  
  .layer-group-header.drag-over {
    box-shadow: 0 6px 20px rgba(25, 118, 210, 0.6);
    transform: scale(1.02);
    border: 2px dashed #1976d2;
  }
  
  .layer-group-header .group-title {
    color: white;
    font-weight: 700;
    font-size: 14px;
    letter-spacing: 0.5px;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  
  .layer-group-header .group-title i {
    font-size: 18px;
  }
  
  .layer-group-header .group-count {
    color: rgba(255, 255, 255, 0.8);
    font-size: 12px;
    margin-left: 8px;
  }
  
  .layer-group-header .group-actions button {
    color: white;
    opacity: 0.9;
    transition: opacity 0.2s;
  }
  
  .layer-group-header .group-actions button:hover {
    opacity: 1;
  }
  
  .layer-group-header .group-chevron {
    color: white;
    font-size: 14px;
    transition: transform 0.3s;
  }
  
  .layer-group-header.expanded .group-chevron {
    transform: rotate(180deg);
  }
  
  /* Layer di dalam group lebih menjorok ke kanan */
  .layer-group-list {
    margin-left: 16px;
    padding-left: 12px;
    border-left: 3px solid #d32f2f;
  }
  
  .layer-group-list .layer-item {
    background: #fff;
    border-left: 3px solid #e0e0e0;
    margin-bottom: 6px;
  }
  .layer-item.drag-over {
    background: #e3f2fd;
    border-color: #1976d2;
    opacity: 0.7;
  }
  
  .layer-item:hover {
    background: #f0f7ff;
    border-color: #a3301c;
    transform: translateX(3px);
    box-shadow: 0 2px 8px rgba(163, 48, 28, 0.15);
  }
  
  .layer-item-name {
    font-weight: 600;
    font-size: 13px;
    margin-bottom: 8px;
    color: #333;
    display: flex;
    align-items: center;
    gap: 8px;
    line-height: 1.4;
  }
  
  .layer-item-name .layer-icon {
    font-size: 16px;
    flex-shrink: 0;
  }
  
  .layer-item-name .layer-text {
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  
  .layer-item-name .badge {
    flex-shrink: 0;
    font-size: 9px;
    padding: 2px 6px;
  }
  
  .layer-item-info {
    font-size: 11px;
    color: #666;
    margin-bottom: 4px;
    line-height: 1.3;
  }
  
  .layer-item-date {
    font-size: 10px;
    color: #999;
    margin-bottom: 0;
    display: flex;
    align-items: center;
    gap: 4px;
  }
  
  .layer-item-date:before {
    content: '📅';
    font-size: 10px;
  }
  
  .layer-item-actions {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    margin-top: 8px;
  }
  
  .layer-item-actions button {
    font-size: 11px;
    padding: 5px 10px;
    border-radius: 4px;
    border: none;
    transition: all 0.2s;
  }
  
  .layer-item-actions button:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.15);
  }
  
  .layer-item-actions button i {
    font-size: 12px;
  }
  
  .layer-color-indicator {
    width: 14px;
    height: 14px;
    border-radius: 3px;
    display: inline-block;
    margin-right: 6px;
    border: 1px solid rgba(0,0,0,0.2);
  }
  
  /* Icon Marker Selection */
  .icon-selector {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin: 10px 0;
  }
  
  .icon-option {
    width: 40px;
    height: 40px;
    border: 2px solid #ddd;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    background: white;
  }
  
  .icon-option:hover {
    border-color: #a3301c;
    transform: scale(1.1);
  }
  
  .icon-option.selected {
    border-color: #a3301c;
    border-width: 3px;
    background: #fff5f5;
  }
  
  .icon-option i {
    font-size: 20px;
    color: #666;
  }
  
  .sidebar-empty {
    text-align: center;
    color: #999;
    padding: 40px 20px;
  }
  
  .sidebar-empty i {
    font-size: 40px;
    margin-bottom: 10px;
    opacity: 0.3;
  }

  .layer-toolbar button {
    font-size: 12px;
  }
  
  /* Optimasi performa sidebar */
  #layerListBody {
    transform: translateZ(0);
    will-change: scroll-position;
  }
  
  /* Custom cluster marker styles */
  .marker-cluster div {
    width: 30px;
    height: 30px;
    margin-left: 5px;
    margin-top: 5px;
    text-align: center;
    border-radius: 15px;
    font: 12px Arial, sans-serif;
    font-weight: bold;
  }
  .marker-cluster-small {
    background-color: rgba(181, 226, 140, 0.6);
  }
  .marker-cluster-small div {
    background-color: rgba(110, 204, 57, 0.8);
    color: #fff;
  }
  .marker-cluster-medium {
    background-color: rgba(241, 211, 87, 0.6);
  }
  .marker-cluster-medium div {
    background-color: rgba(240, 194, 12, 0.8);
    color: #fff;
  }
  .marker-cluster-large {
    background-color: rgba(253, 156, 115, 0.6);
  }
  .marker-cluster-large div {
    background-color: rgba(241, 128, 23, 0.8);
    color: #fff;
  }
  
  /* SweetAlert Edit Dialog - Floating panel without backdrop */
  .swal-edit-container {
    z-index: 10000 !important;
    pointer-events: none !important; /* Let clicks pass through container */
  }
  
  .swal-edit-popup {
    margin: 10px !important;
    max-height: calc(100vh - 20px) !important;
    overflow-y: auto !important;
    pointer-events: all !important; /* But make popup itself clickable */
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4) !important;
    border: 2px solid #1976d2 !important;
  }
  
  /* NO backdrop - map stays fully interactive */
  .swal2-container.swal-edit-container {
    background-color: transparent !important;
  }
  
  /* Highlight Geoman edit mode markers */
  .leaflet-pm-action-drag,
  .marker-icon.leaflet-pm-draggable {
    cursor: move !important;
  }
  
  .leaflet-marker-icon.leaflet-pm-draggable {
    filter: drop-shadow(0 0 8px #4CAF50) !important;
  }
</style>

<div class="card">
  <div class="map-layout-container">
    <!-- Layer Sidebar -->
    <div id="layerSidebar">
      <div class="sidebar-header" onclick="toggleLayerSidebar()">
        <div class="sidebar-title">
          <i class="fas fa-layer-group"></i>
          <span>Daftar Layer</span>
        </div>
        <i class="fas fa-chevron-left" id="sidebarToggleIcon"></i>
      </div>
      <div class="sidebar-body">
        <!-- Modern Location Search -->
        <div class="location-search-container mb-3">
          <div class="location-search-wrapper">
            <i class="fas fa-map-marker-alt search-icon"></i>
            <input type="text" id="locationSearchInput" class="location-search-input" placeholder="Cari alamat atau lokasi...">
            <button class="search-clear-btn" onclick="clearLocationSearch()" style="display: none;">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div id="searchResultsDropdown" class="search-results-dropdown"></div>
        </div>
        <style>
          .location-search-container {
            position: relative;
          }
          
          .location-search-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 0 12px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
          }
          
          .location-search-wrapper:focus-within {
            border-color: #1976d2;
            box-shadow: 0 4px 12px rgba(25, 118, 210, 0.2);
          }
          
          .location-search-wrapper .search-icon {
            color: #1976d2;
            font-size: 16px;
            margin-right: 10px;
          }
          
          .location-search-input {
            flex: 1;
            border: none;
            outline: none;
            padding: 12px 0;
            font-size: 14px;
            background: transparent;
          }
          
          .location-search-input::placeholder {
            color: #999;
          }
          
          .search-clear-btn {
            background: #f5f5f5;
            border: none;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            margin-left: 8px;
          }
          
          .search-clear-btn:hover {
            background: #e0e0e0;
          }
          
          .search-clear-btn i {
            font-size: 12px;
            color: #666;
          }
          
          .search-results-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
            margin-top: 8px;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
          }
          
          .search-results-dropdown.show {
            display: block;
          }
          
          .search-result-item {
            padding: 12px 16px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.2s;
            display: flex;
            align-items: start;
            gap: 12px;
          }
          
          .search-result-item:last-child {
            border-bottom: none;
          }
          
          .search-result-item:hover {
            background: #f5f9ff;
          }
          
          .search-result-icon {
            color: #1976d2;
            font-size: 16px;
            margin-top: 2px;
            flex-shrink: 0;
          }
          
          .search-result-content {
            flex: 1;
            min-width: 0;
          }
          
          .search-result-title {
            font-weight: 600;
            font-size: 13px;
            color: #333;
            margin-bottom: 2px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
          }
          
          .search-result-address {
            font-size: 11px;
            color: #666;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
          }
          
          .search-loading {
            padding: 16px;
            text-align: center;
            color: #999;
            font-size: 13px;
          }
          
          .search-no-results {
            padding: 20px;
            text-align: center;
            color: #999;
          }
          
          .search-no-results i {
            font-size: 32px;
            margin-bottom: 8px;
            opacity: 0.3;
          }
        </style>
        <script>
          let searchTimeout = null;
          
          // Location search dengan Nominatim API langsung
          document.getElementById('locationSearchInput').addEventListener('input', function(e) {
            const query = e.target.value.trim();
            const clearBtn = document.querySelector('.search-clear-btn');
            const dropdown = document.getElementById('searchResultsDropdown');
            
            // Show/hide clear button
            clearBtn.style.display = query ? 'flex' : 'none';
            
            // Clear previous timeout
            if (searchTimeout) clearTimeout(searchTimeout);
            
            if (query.length < 3) {
              dropdown.classList.remove('show');
              return;
            }
            
            // Show loading
            dropdown.innerHTML = '<div class="search-loading"><i class="fas fa-spinner fa-spin"></i> Mencari...</div>';
            dropdown.classList.add('show');
            
            // Debounce search
            searchTimeout = setTimeout(() => {
              performLocationSearch(query);
            }, 500);
          });
          
          // Handle Enter key
          document.getElementById('locationSearchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
              const query = e.target.value.trim();
              if (query.length >= 3) {
                performLocationSearch(query);
              }
            }
          });
          
          function performLocationSearch(query) {
            const dropdown = document.getElementById('searchResultsDropdown');
            
            console.log('🔍 Searching Nominatim for:', query);
            
            // Nominatim OpenStreetMap API
            const url = `https://nominatim.openstreetmap.org/search?format=json&limit=5&q=${encodeURIComponent(query)}`;
            
            fetch(url, {
              headers: {
                'User-Agent': 'KencanaMapApp/1.0' // Nominatim meminta User-Agent
              }
            })
            .then(response => response.json())
            .then(results => {
              console.log('📦 Nominatim results:', results);
              
              if (!results || results.length === 0) {
                dropdown.innerHTML = '<div class="search-no-results"><i class="fas fa-map-marker-alt"></i><div>Lokasi tidak ditemukan</div></div>';
                return;
              }
              
              // Render results
              let html = '';
              results.forEach((result) => {
                const name = result.display_name;
                const lat = parseFloat(result.lat);
                const lon = parseFloat(result.lon);
                const escapedName = name.replace(/'/g, "\\'");
                
                html += `
                  <div class="search-result-item" onclick="selectSearchResult(${lat}, ${lon}, '${escapedName}')">
                    <i class="fas fa-map-marker-alt search-result-icon"></i>
                    <div class="search-result-content">
                      <div class="search-result-title">${result.name || result.type}</div>
                      <div class="search-result-address">${name}</div>
                    </div>
                  </div>
                `;
              });
              
              console.log('✅ Rendering', results.length, 'results');
              dropdown.innerHTML = html;
            })
            .catch(error => {
              console.error('❌ Search error:', error);
              dropdown.innerHTML = '<div class="search-no-results"><i class="fas fa-exclamation-circle"></i><div>Terjadi kesalahan pencarian</div></div>';
            });
          }
          
          function selectSearchResult(lat, lng, name) {
            console.log('📍 Selected location:', name, lat, lng);
            
            // Zoom to location
            map.setView([lat, lng], 16);
            
            // Add temporary marker
            const marker = L.marker([lat, lng], {
              icon: L.divIcon({
                html: '<i class="fas fa-map-marker-alt" style="color: #e74c3c; font-size: 32px; text-shadow: 0 0 3px white;"></i>',
                className: 'search-result-marker',
                iconSize: [32, 32],
                iconAnchor: [16, 32]
              })
            }).addTo(map);
            
            marker.bindPopup(`<strong>Lokasi Dipilih</strong><br>${name}<br><br>Lat: ${lat.toFixed(6)}<br>Lng: ${lng.toFixed(6)}`).openPopup();
            
            // Remove marker after 10 seconds
            setTimeout(() => {
              map.removeLayer(marker);
            }, 10000);
            
            // Close dropdown
            document.getElementById('searchResultsDropdown').classList.remove('show');
            
            // Update input dengan nama singkat (ambil part pertama saja)
            const shortName = name.split(',')[0];
            document.getElementById('locationSearchInput').value = shortName;
          }
          
          function clearLocationSearch() {
            document.getElementById('locationSearchInput').value = '';
            document.querySelector('.search-clear-btn').style.display = 'none';
            document.getElementById('searchResultsDropdown').classList.remove('show');
          }
          
          // Close dropdown when clicking outside
          document.addEventListener('click', function(e) {
            const container = document.querySelector('.location-search-container');
            if (container && !container.contains(e.target)) {
              document.getElementById('searchResultsDropdown').classList.remove('show');
            }
          });
        </script>
        
        <div class="layer-toolbar mb-2" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
          <div style="position: relative;">
            <button class="btn btn-sm btn-secondary" id="importExportMenuBtn" onclick="toggleImportExportMenu()" title="Menu Import/Export"><i class="fas fa-ellipsis-h"></i></button>
            <div id="importExportMenu" style="display:none; position:absolute; left:0; top:110%; z-index:10; background:#fff; border:1px solid #ddd; border-radius:6px; box-shadow:0 2px 8px rgba(0,0,0,0.08); min-width:120px; padding:8px 0;">
              <button class="btn btn-sm btn-primary w-100 mb-1" onclick="triggerImportKml(); closeImportExportMenu();">Import KML/KMZ</button>
              <button class="btn btn-sm btn-success w-100" onclick="exportLayersToKml(); closeImportExportMenu();">Export KML</button>
              <input type="file" id="kmlFileInput" accept=".kml,.kmz" style="display:none" onchange="handleKmlImport(event)">
            </div>
          </div>
        </div>
        <script>
        // Collapsible import/export menu
        function toggleImportExportMenu() {
          const menu = document.getElementById('importExportMenu');
          if (!menu) return;
          menu.style.display = (menu.style.display === 'none' || !menu.style.display) ? 'block' : 'none';
        }
        function closeImportExportMenu() {
          const menu = document.getElementById('importExportMenu');
          if (menu) menu.style.display = 'none';
        }
        // Close menu if click outside
        document.addEventListener('click', function(e) {
          const btn = document.getElementById('importExportMenuBtn');
          const menu = document.getElementById('importExportMenu');
          if (!btn || !menu) return;
          if (!btn.contains(e.target) && !menu.contains(e.target)) {
            menu.style.display = 'none';
          }
        });
        </script>
        <div class="mb-2">
          <div class="input-group input-group-sm mb-2">
            <input id="layerSearchInput" type="text" class="form-control form-control-sm" placeholder="Cari layer..." oninput="filterLayerList()">
          </div>
          <button class="btn btn-create-group w-100" type="button" onclick="showCreateGroupInput()">
            <i class="fas fa-folder-plus me-2"></i>
            <span>Buat Group Baru</span>
          </button>
        </div>
        <style>
          .btn-create-group {
            background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
            color: white;
            border: none;
            padding: 10px 16px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(25, 118, 210, 0.3);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
          }
          
          .btn-create-group:hover {
            background: linear-gradient(135deg, #1565c0 0%, #0d47a1 100%);
            box-shadow: 0 5px 15px rgba(25, 118, 210, 0.4);
            transform: translateY(-2px);
            color: white;
          }
          
          .btn-create-group:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(25, 118, 210, 0.3);
          }
          
          .btn-create-group i {
            font-size: 15px;
          }
        </style>
        <div id="createGroupInputBox" class="mb-2" style="display:none;">
          <div class="input-group input-group-sm mb-2">
            <input id="newGroupNameInput" type="text" class="form-control" placeholder="Nama Group" maxlength="32">
          </div>
          <div class="d-flex gap-2 align-items-center mb-2">
            <label class="small mb-0" style="min-width: 80px;">Warna Group:</label>
            <input id="newGroupColorInput" type="color" value="#d32f2f" class="form-control form-control-sm" style="width: 60px; height: 32px;">
            <div class="d-flex gap-1">
              <button class="btn btn-sm" style="background: #d32f2f; width: 24px; height: 24px; padding: 0; border: 2px solid #fff; box-shadow: 0 0 0 1px #ddd;" onclick="document.getElementById('newGroupColorInput').value='#d32f2f'"></button>
              <button class="btn btn-sm" style="background: #1976d2; width: 24px; height: 24px; padding: 0; border: 2px solid #fff; box-shadow: 0 0 0 1px #ddd;" onclick="document.getElementById('newGroupColorInput').value='#1976d2'"></button>
              <button class="btn btn-sm" style="background: #388e3c; width: 24px; height: 24px; padding: 0; border: 2px solid #fff; box-shadow: 0 0 0 1px #ddd;" onclick="document.getElementById('newGroupColorInput').value='#388e3c'"></button>
              <button class="btn btn-sm" style="background: #f57c00; width: 24px; height: 24px; padding: 0; border: 2px solid #fff; box-shadow: 0 0 0 1px #ddd;" onclick="document.getElementById('newGroupColorInput').value='#f57c00'"></button>
              <button class="btn btn-sm" style="background: #7b1fa2; width: 24px; height: 24px; padding: 0; border: 2px solid #fff; box-shadow: 0 0 0 1px #ddd;" onclick="document.getElementById('newGroupColorInput').value='#7b1fa2'"></button>
            </div>
          </div>
          <div class="d-flex gap-2">
            <button class="btn btn-primary btn-sm flex-fill" onclick="createGroupFromInput()">Buat</button>
            <button class="btn btn-light btn-sm flex-fill" onclick="hideCreateGroupInput()">Batal</button>
          </div>
        </div>
        <div id="layerListBody">
          <div class="sidebar-empty">
            <i class="fas fa-spinner fa-spin"></i>
            <div>Memuat layer...</div>
          </div>
        </div>
      <script>
      // --- GROUPING LAYER SIDEBAR ---
      function renderLayerListGrouped() {
        console.log('🔄 renderLayerListGrouped() CALLED');
        const layers = window.savedLayersData || [];
        const groupMap = {};
        const noGroup = [];
        
        console.log('📊 Total layers to render:', layers.length); // Debug
        
        // Ambil semua group dari layer type: 'group'
        const groupNames = layers.filter(l => l.type === 'group').map(l => l.name);
        
        console.log('📁 Group names found:', groupNames); // Debug
        
        // Pisahkan layer berdasarkan group
        layers.forEach(l => {
          // SKIP jika type === 'group' (ini adalah group header, bukan layer)
          if (l.type === 'group') {
            console.log('Skipping group type:', l.name);
            return;
          }
          
          // Jika layer punya group field, masukkan ke groupMap
          if (l.group && groupNames.includes(l.group)) {
            if (!groupMap[l.group]) groupMap[l.group] = [];
            groupMap[l.group].push(l);
            console.log('Added to group', l.group, ':', l.name);
          } else {
            // Layer tanpa group
            noGroup.push(l);
            console.log('Added to noGroup:', l.name);
          }
        });
        
        // Pastikan group tampil di sidebar walau belum ada layer di dalamnya
        groupNames.forEach(g => { 
          if (!groupMap[g]) groupMap[g] = []; 
        });
        
        const body = document.getElementById('layerListBody');
        if (!body) {
          console.error('❌ layerListBody element not found!');
          return;
        }
        
        console.log('✅ layerListBody found, building HTML...');
        
        let html = '';
        
        // Render semua group dengan header merah
        groupNames.forEach(groupName => {
          const groupId = 'group_' + btoa(groupName).replace(/[^a-zA-Z0-9]/g, '');
          const layersInGroup = groupMap[groupName] || [];
          const escapedGroupName = groupName.replace(/'/g, "\\'");
          
          // Cari data group untuk ambil warna
          const groupData = layers.find(l => l.type === 'group' && l.name === groupName);
          const groupColor = groupData?.color || '#d32f2f';
          const groupColorDark = adjustBrightness(groupColor, -20);
          
          console.log(`📦 Rendering group: "${groupName}" (${layersInGroup.length} layers) - Color: ${groupColor}`);
          
          // Check localStorage for expand state
          const isExpanded = getGroupExpandState(groupName);
          const displayStyle = isExpanded ? 'block' : 'none';
          const expandedClass = isExpanded ? 'expanded' : '';
          
          html += `<div class="layer-group" id="${groupId}">
            <div class="layer-group-header ${expandedClass}" style="background: linear-gradient(135deg, ${groupColor} 0%, ${groupColorDark} 100%);" onclick="toggleLayerGroup('${groupId}')" 
                 ondragover="event.preventDefault(); event.stopPropagation(); event.currentTarget.classList.add('drag-over');" 
                 ondragleave="event.currentTarget.classList.remove('drag-over');"
                 ondrop="onDropLayerToGroup(event, '${escapedGroupName}')">
              <div class="d-flex align-items-center justify-content-between">
                <div class="group-title">
                  <i class='fas fa-folder'></i>
                  <span>${groupName}</span>
                  <span class='group-count'>(${layersInGroup.length})</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <div class="group-actions">
                    <button class='btn btn-xs btn-link p-0' title='Edit Warna Group' onclick="event.stopPropagation();editGroupColor('${escapedGroupName}', '${groupColor}')"><i class='fas fa-palette'></i></button>
                    <button class='btn btn-xs btn-link p-0 ms-2' title='Show/Hide Group' onclick="event.stopPropagation();toggleGroupVisibility('${escapedGroupName}')"><i class='fas fa-eye'></i></button>
                    <button class='btn btn-xs btn-link p-0 ms-2' title='Delete Group' onclick="event.stopPropagation();deleteGroupLayers('${escapedGroupName}')"><i class='fas fa-trash-alt'></i></button>
                  </div>
                  <i class='fas fa-chevron-down group-chevron ms-2'></i>
                </div>
              </div>
            </div>
            <div class="layer-group-list" style="display:${displayStyle};" 
                 ondragover="event.preventDefault(); event.stopPropagation(); event.currentTarget.classList.add('drag-over');" 
                 ondragleave="event.currentTarget.classList.remove('drag-over');"
                 ondrop="onDropLayerToGroup(event, '${escapedGroupName}')">
              ${layersInGroup.map(l => renderLayerItemHTML(l)).join('')}
            </div>
          </div>`;
        });
        
        // Area drop untuk keluar group
        if (groupNames.length > 0) {
          html += `<div class='no-group-drop-area' ondragover='event.preventDefault()' ondrop='onDropLayerToNoGroup(event)' style='min-height:30px; background:#f8f9fa; border:1px dashed #ccc; border-radius:6px; margin-bottom:8px; text-align:center; color:#aaa; font-size:13px; padding:8px;'>Drop layer di sini untuk keluar group</div>`;
        }
        
        // Render layer tanpa group
        noGroup.forEach(l => { 
          html += renderLayerItemHTML(l); 
        });
        
        console.log('🎨 Final HTML length:', html.length);
        console.log('📌 Sample HTML:', html.slice(0, 300));
        
        body.innerHTML = html || `<div class='sidebar-empty'><i class='fas fa-layer-group'></i><div>Belum ada layer</div></div>`;
      }
      window.showCreateGroupInput = function() {
        document.getElementById('createGroupInputBox').style.display = '';
        document.getElementById('newGroupNameInput').focus();
      }
      window.hideCreateGroupInput = function() {
        document.getElementById('createGroupInputBox').style.display = 'none';
        document.getElementById('newGroupNameInput').value = '';
      }
      window.createGroupFromInput = function() {
        const name = document.getElementById('newGroupNameInput').value.trim();
        const color = document.getElementById('newGroupColorInput').value || '#d32f2f';
        if (!name) {
          alert('Nama group harus diisi!');
          return;
        }
        $.ajax({
          url: '/map/layers',
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': window.csrfToken },
          data: {
            name: name,
            type: 'group',
            coordinates: '[]',
            color: color,
            icon: 'pin',
            weight: 3,
            opacity: 0.6,
            description: 'Group Background Color'
          },
          success: function(res) {
            hideCreateGroupInput();
            loadSavedLayers();
            Swal.fire({
              icon: 'success',
              title: 'Berhasil!',
              text: 'Group berhasil dibuat',
              timer: 1500,
              showConfirmButton: false
            });
          },
          error: function(xhr) {
            Swal.fire({
              icon: 'error',
              title: 'Gagal!',
              text: 'Gagal membuat group: ' + xhr.responseText,
              confirmButtonText: 'OK'
            });
          }
        });
      }

      // --- DRAG & DROP LAYER TO GROUP ---
      let draggedLayerId = null;
      function onDragLayer(e, layerId) {
        draggedLayerId = layerId;
        e.dataTransfer.effectAllowed = 'move';
        console.log('🎬 Drag started - Layer ID:', layerId);
      }
      function onDropLayerToGroup(e, groupName) {
        e.preventDefault();
        e.stopPropagation();
        
        // Remove visual feedback
        e.currentTarget.classList.remove('drag-over');
        
        console.log('🎯 onDropLayerToGroup called - Layer ID:', draggedLayerId, 'Group:', groupName);
        if (draggedLayerId == null) {
          console.warn('❌ draggedLayerId is null, aborting');
          return;
        }
        const idx = (window.savedLayersData||[]).findIndex(l => l.id === draggedLayerId);
        console.log('📍 Found layer at index:', idx);
        if (idx >= 0) {
          window.savedLayersData[idx].group = groupName;
          console.log('✅ Updated layer group in memory:', window.savedLayersData[idx].name, '-> group:', groupName);
          updateLayerGroupBackend(draggedLayerId, groupName);
          renderLayerListGrouped();
        } else {
          console.error('❌ Layer not found in savedLayersData');
        }
        draggedLayerId = null;
      }
      function onDropLayerToNoGroup(e) {
        e.preventDefault();
        if (draggedLayerId == null) return;
        const idx = (window.savedLayersData||[]).findIndex(l => l.id === draggedLayerId);
        if (idx >= 0) {
          window.savedLayersData[idx].group = null;
          updateLayerGroupBackend(draggedLayerId, null);
          renderLayerListGrouped();
        }
        draggedLayerId = null;
      }
      function updateLayerGroupBackend(layerId, groupName) {
        console.log('💾 Updating backend - Layer ID:', layerId, 'Group:', groupName);
        $.ajax({
          url: '/map/layers/' + layerId,
          method: 'PATCH',
          headers: { 'X-CSRF-TOKEN': window.csrfToken },
          data: { group: groupName },
          success: function(res) {
            console.log('✅ Backend update successful:', res);
          },
          error: function(xhr) {
            console.error('❌ Backend update failed:', xhr.responseText);
            alert('Gagal update group layer: ' + xhr.responseText);
          }
        });
      }

      function renderLayerItemHTML(layer) {
        // Render layer item (bukan group header)
        // Group header di-render terpisah di renderLayerListGrouped()
        
        // Skip jika type group (safety check)
        if (layer.type === 'group') {
          console.warn('renderLayerItemHTML called with group type, skipping:', layer.name);
          return '';
        }
        
        const name = layer.name || ('Layer #' + layer.id);
        const desc = layer.description ? `<div class='layer-item-info'>${layer.description}</div>` : '';
        const date = `<div class='layer-item-date'>${new Date(layer.created_at).toLocaleDateString('id-ID')}</div>`;
        
        let typeIcon = '';
        let typeLabel = '';
        
        if (layer.type === 'marker' && layer.icon === 'text') {
          // Text label (marker dengan icon='text')
          typeIcon = '📝';
          typeLabel = '<span class="badge bg-warning ms-1">Text</span>';
        } else if (layer.type === 'marker') {
          typeIcon = '📍';
          typeLabel = '<span class="badge bg-info ms-1">Marker</span>';
        } else if (layer.type === 'polyline') {
          typeIcon = '🪢';
          typeLabel = '<span class="badge bg-primary ms-1">Polyline</span>';
        } else if (layer.type === 'polygon') {
          typeIcon = '⬛';
          typeLabel = '<span class="badge bg-success ms-1">Polygon</span>';
        } else if (layer.type === 'rectangle') {
          typeIcon = '▭';
          typeLabel = '<span class="badge bg-success ms-1">Rectangle</span>';
        } else if (layer.type === 'text') {
          typeIcon = '📝';
          typeLabel = '<span class="badge bg-warning ms-1">Text</span>';
        } else {
          typeIcon = '📄';
          typeLabel = '<span class="badge bg-secondary ms-1">' + (layer.type || 'Layer') + '</span>';
        }
        
        // Cek visibility status dari localStorage
        const isVisible = getLayerVisibility(layer.id);
        const eyeIcon = isVisible ? 'fa-eye' : 'fa-eye-slash';
        const eyeTitle = isVisible ? 'Hide Layer' : 'Show Layer';
        
        return `<div class="layer-item" data-layer-id="${layer.id}" draggable="true" ondragstart="onDragLayer(event, ${layer.id})" ondragover="onLayerDragOver(event)" ondragleave="onLayerDragLeave(event)" ondrop="onLayerDrop(event, ${layer.id})">
          <div class="layer-item-name">
            <span class="layer-icon">${typeIcon}</span>
            <span class="layer-text">${name}</span>
            ${typeLabel}
          </div>
          ${desc}
          ${date}
          <div class="layer-item-actions">
            <button class="btn btn-sm btn-info" title="Zoom" onclick="event.stopPropagation();zoomToLayer(${layer.id})"><i class="fas fa-search-plus"></i></button>
            <button class="btn btn-sm btn-warning" title="${eyeTitle}" onclick="event.stopPropagation();toggleLayerVisibility(${layer.id})"><i class="fas ${eyeIcon}"></i></button>
            <button class="btn btn-sm btn-primary" title="Edit" onclick="event.stopPropagation();editLayer(${layer.id})"><i class="fas fa-edit"></i></button>
            <button class="btn btn-sm btn-danger" title="Delete" onclick="event.stopPropagation();deleteLayer(${layer.id})"><i class="fas fa-trash"></i></button>
          </div>
        </div>`;
      }

      // --- DRAG & DROP LAYER REORDERING (antar layer) ---
      window.onLayerDragOver = function(e) {
        e.preventDefault();
        e.currentTarget.classList.add('drag-over');
      };
      
      window.onLayerDragLeave = function(e) {
        e.currentTarget.classList.remove('drag-over');
      };
      
      window.onLayerDrop = function(e, targetLayerId) {
        e.preventDefault();
        e.currentTarget.classList.remove('drag-over');
        
        if (draggedLayerId == null || draggedLayerId === targetLayerId) return;
        
        // Reorder logic bisa ditambahkan di sini jika diperlukan
        // Untuk saat ini, fungsi ini hanya mencegah error
        console.log('Dropped layer', draggedLayerId, 'onto layer', targetLayerId);
        
        draggedLayerId = null;
      };

      function toggleLayerGroup(groupId) {
        const el = document.querySelector(`#${groupId} .layer-group-list`);
        const header = document.querySelector(`#${groupId} .layer-group-header`);
        if (el) {
          const isHidden = el.style.display === 'none' || !el.style.display;
          el.style.display = isHidden ? 'block' : 'none';
          if (header) {
            if (isHidden) {
              header.classList.add('expanded');
            } else {
              header.classList.remove('expanded');
            }
          }
          
          // Save state to localStorage
          // Extract group name from groupId (remove 'group_' prefix and decode base64)
          const groupName = el.closest('.layer-group').querySelector('.group-title span').textContent;
          setGroupExpandState(groupName, isHidden); // isHidden becomes isExpanded
          console.log('💾 Saved group expand state:', groupName, '=', isHidden);
        }
      }

      function toggleGroupVisibility(groupName) {
        console.log('👁️ toggleGroupVisibility called for group:', groupName);
        const layers = (window.savedLayersData || []).filter(l => l.group === groupName && l.type !== 'group');
        console.log('📦 Found', layers.length, 'layers in group');
        
        // Cek apakah ada layer yang visible
        let anyVisible = false;
        layers.forEach(l => {
          const leafletLayer = window.layerObjects[l.id];
          if (!leafletLayer) return;
          const isMarker = l.type === 'marker';
          if (isMarker) {
            if (window.importedMarkerCluster.hasLayer(leafletLayer)) anyVisible = true;
          } else {
            if (savedLayersGroup.hasLayer(leafletLayer)) anyVisible = true;
          }
        });
        
        console.log(anyVisible ? '🙈 Hiding all layers in group' : '👀 Showing all layers in group');
        
        // Toggle semua layer di group
        layers.forEach(l => {
          const leafletLayer = window.layerObjects[l.id];
          if (!leafletLayer) return;
          const isMarker = l.type === 'marker';
          
          if (anyVisible) {
            // Hide layer
            if (isMarker) {
              window.importedMarkerCluster.removeLayer(leafletLayer);
            } else {
              savedLayersGroup.removeLayer(leafletLayer);
            }
            // Also remove from map directly if still there
            if (map.hasLayer(leafletLayer)) {
              map.removeLayer(leafletLayer);
            }
          } else {
            // Show layer - remove from map first to prevent double-add
            if (map.hasLayer(leafletLayer)) {
              map.removeLayer(leafletLayer);
            }
            if (isMarker) {
              window.importedMarkerCluster.addLayer(leafletLayer);
            } else {
              savedLayersGroup.addLayer(leafletLayer);
            }
          }
        });
        
        // Re-render untuk update icon mata di semua layer
        renderLayerListGrouped();
      }

      function deleteGroupLayers(groupName) {
        if (!confirm('Hapus semua layer dalam group "' + groupName + '"?')) return;
        const layers = (window.savedLayersData || []).filter(l => l.group === groupName);
        let count = 0;
        layers.forEach(l => {
          deleteLayer(l.id);
          count++;
        });
        setTimeout(() => renderLayerListGrouped(), 500); // Refresh UI
      }
      
      function editGroupColor(groupName, currentColor) {
        Swal.fire({
          title: 'Edit Warna Group',
          html: `
            <div class="mb-3">
              <label class="form-label">Pilih warna untuk "${groupName}":</label>
              <input type="color" id="swal-group-color" value="${currentColor}" class="form-control" style="height: 50px;">
            </div>
            <div class="d-flex gap-2 justify-content-center mt-3">
              <button class="btn btn-sm" style="background: #d32f2f; width: 36px; height: 36px; border: 2px solid #fff; box-shadow: 0 0 0 1px #ddd;" onclick="document.getElementById('swal-group-color').value='#d32f2f'"></button>
              <button class="btn btn-sm" style="background: #1976d2; width: 36px; height: 36px; border: 2px solid #fff; box-shadow: 0 0 0 1px #ddd;" onclick="document.getElementById('swal-group-color').value='#1976d2'"></button>
              <button class="btn btn-sm" style="background: #388e3c; width: 36px; height: 36px; border: 2px solid #fff; box-shadow: 0 0 0 1px #ddd;" onclick="document.getElementById('swal-group-color').value='#388e3c'"></button>
              <button class="btn btn-sm" style="background: #f57c00; width: 36px; height: 36px; border: 2px solid #fff; box-shadow: 0 0 0 1px #ddd;" onclick="document.getElementById('swal-group-color').value='#f57c00'"></button>
              <button class="btn btn-sm" style="background: #7b1fa2; width: 36px; height: 36px; border: 2px solid #fff; box-shadow: 0 0 0 1px #ddd;" onclick="document.getElementById('swal-group-color').value='#7b1fa2'"></button>
              <button class="btn btn-sm" style="background: #424242; width: 36px; height: 36px; border: 2px solid #fff; box-shadow: 0 0 0 1px #ddd;" onclick="document.getElementById('swal-group-color').value='#424242'"></button>
            </div>
          `,
          showCancelButton: true,
          confirmButtonText: 'Simpan',
          cancelButtonText: 'Batal',
          preConfirm: () => {
            const newColor = document.getElementById('swal-group-color').value;
            return newColor;
          }
        }).then((result) => {
          if (result.isConfirmed) {
            const newColor = result.value;
            // Cari group layer untuk update
            const groupLayer = (window.savedLayersData || []).find(l => l.type === 'group' && l.name === groupName);
            if (groupLayer) {
              $.ajax({
                url: '/map/layers/' + groupLayer.id,
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': window.csrfToken },
                data: { color: newColor },
                success: function(res) {
                  Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Warna group berhasil diubah',
                    timer: 1500,
                    showConfirmButton: false
                  });
                  loadSavedLayers();
                },
                error: function(xhr) {
                  Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: 'Gagal mengubah warna: ' + xhr.responseText,
                    confirmButtonText: 'OK'
                  });
                }
              });
            }
          }
        });
      }

      // Helper function to adjust color brightness for gradient
      function adjustBrightness(color, percent) {
        const num = parseInt(color.replace('#', ''), 16);
        const amt = Math.round(2.55 * percent);
        const R = (num >> 16) + amt;
        const G = (num >> 8 & 0x00FF) + amt;
        const B = (num & 0x0000FF) + amt;
        return '#' + (0x1000000 + (R < 255 ? R < 1 ? 0 : R : 255) * 0x10000 +
          (G < 255 ? G < 1 ? 0 : G : 255) * 0x100 +
          (B < 255 ? B < 1 ? 0 : B : 255))
          .toString(16).slice(1);
      }
      
      // LocalStorage functions for visibility state persistence
      function getLayerVisibility(layerId) {
        const stored = localStorage.getItem('layerVisibility_' + layerId);
        return stored === null ? true : stored === 'true'; // Default visible
      }
      
      function setLayerVisibility(layerId, isVisible) {
        localStorage.setItem('layerVisibility_' + layerId, isVisible.toString());
      }
      
      // LocalStorage functions for group expand/collapse state persistence
      function getGroupExpandState(groupName) {
        const stored = localStorage.getItem('groupExpand_' + groupName);
        return stored === null ? false : stored === 'true'; // Default collapsed
      }
      
      function setGroupExpandState(groupName, isExpanded) {
        localStorage.setItem('groupExpand_' + groupName, isExpanded.toString());
      }

      function toggleLayerVisibility(layerId) {
        console.log('👁️ toggleLayerVisibility called for layer:', layerId);
        const leafletLayer = window.layerObjects[layerId];
        if (!leafletLayer) {
          console.warn('❌ Layer not found in layerObjects');
          return;
        }
        
        // Cek type layer dulu
        const layer = (window.savedLayersData || []).find(l => l.id === layerId);
        if (!layer) {
          console.warn('❌ Layer data not found in savedLayersData');
          return;
        }
        
        const isMarker = layer.type === 'marker';
        let isVisible = false;
        
        // Cek visibility berdasarkan type
        if (isMarker) {
          isVisible = window.importedMarkerCluster.hasLayer(leafletLayer);
        } else {
          isVisible = savedLayersGroup.hasLayer(leafletLayer);
        }
        
        console.log('📍 Current visibility:', isVisible);
        console.log('🗺️ Layer on map directly?', map.hasLayer(leafletLayer));
        console.log('📦 Layer in cluster/group?', isMarker ? window.importedMarkerCluster.hasLayer(leafletLayer) : savedLayersGroup.hasLayer(leafletLayer));
        
        if (isVisible) {
          console.log('🙈 Hiding layer');
          
          // Method 1: Remove from cluster/group
          if (isMarker) {
            window.importedMarkerCluster.removeLayer(leafletLayer);
          } else {
            savedLayersGroup.removeLayer(leafletLayer);
          }
          
          // Method 2: Force remove from map directly
          if (map.hasLayer(leafletLayer)) {
            console.log('⚠️ Layer was also on map directly, removing...');
            map.removeLayer(leafletLayer);
          }
          
          // Method 3: AGGRESSIVE - Set opacity to 0 as fallback
          if (leafletLayer.setOpacity) {
            leafletLayer.setOpacity(0);
            console.log('🎨 Set opacity to 0');
          }
          if (leafletLayer.setStyle) {
            leafletLayer.setStyle({ opacity: 0, fillOpacity: 0 });
            console.log('🎨 Set style opacity to 0');
          }
          
          // Method 4: NUCLEAR - Hide DOM element directly
          if (leafletLayer._icon) {
            leafletLayer._icon.style.display = 'none';
            console.log('💥 Hid marker icon via DOM');
          }
          if (leafletLayer._path) {
            leafletLayer._path.style.display = 'none';
            console.log('💥 Hid path via DOM');
          }
          
          // Save state to localStorage
          setLayerVisibility(layerId, false);
          console.log('💾 Saved hidden state to localStorage');
          
        } else {
          console.log('👀 Showing layer');
          
          // Method 1: Restore opacity
          if (leafletLayer.setOpacity) {
            const originalOpacity = layer.opacity || 0.6;
            leafletLayer.setOpacity(originalOpacity);
            console.log('🎨 Restored opacity to', originalOpacity);
          }
          if (leafletLayer.setStyle) {
            const originalOpacity = layer.opacity || 0.6;
            leafletLayer.setStyle({ 
              opacity: originalOpacity, 
              fillOpacity: originalOpacity * 0.5 
            });
            console.log('🎨 Restored style opacity');
          }
          
          // Method 2: Show DOM element
          if (leafletLayer._icon) {
            leafletLayer._icon.style.display = '';
            console.log('💥 Showed marker icon via DOM');
          }
          if (leafletLayer._path) {
            leafletLayer._path.style.display = '';
            console.log('💥 Showed path via DOM');
          }
          
          // Method 3: Remove from map first to prevent double-add
          if (map.hasLayer(leafletLayer)) {
            console.log('⚠️ Removing from map first to prevent double-add');
            map.removeLayer(leafletLayer);
          }
          
          // Method 4: Add to cluster/group
          if (isMarker) {
            window.importedMarkerCluster.addLayer(leafletLayer);
          } else {
            savedLayersGroup.addLayer(leafletLayer);
          }
          
          // Save state to localStorage
          setLayerVisibility(layerId, true);
          console.log('💾 Saved visible state to localStorage');
        }
        
        console.log('🔍 After toggle - Layer on map?', map.hasLayer(leafletLayer));
        console.log('🔍 After toggle - Layer in cluster/group?', isMarker ? window.importedMarkerCluster.hasLayer(leafletLayer) : savedLayersGroup.hasLayer(leafletLayer));
        
        // Re-render untuk update icon mata
        renderLayerListGrouped();
      }

      function zoomToLayer(layerId) {
        console.log('🔍 Zooming to layer:', layerId);
        const leafletLayer = window.layerObjects[layerId];
        if (!leafletLayer) {
          console.warn('❌ Layer not found in layerObjects');
          return;
        }
        if (leafletLayer.getBounds) map.fitBounds(leafletLayer.getBounds());
        else if (leafletLayer.getLatLng) map.setView(leafletLayer.getLatLng(), 18);
      }
      
      window.editLayer = function(layerId) {
        console.log('✏️ Edit layer:', layerId);
        const layer = (window.savedLayersData || []).find(l => l.id === layerId);
        if (!layer) {
          console.error('❌ Layer not found in savedLayersData');
          Swal.fire('Error', 'Layer tidak ditemukan', 'error');
          return;
        }
        
        const leafletLayer = window.layerObjects[layerId];
        if (!leafletLayer) {
          console.error('❌ Leaflet layer not found');
          Swal.fire('Error', 'Layer tidak ditemukan di peta', 'error');
          return;
        }
        
        console.log('📊 Layer data:', layer);
        console.log('📍 Leaflet layer:', leafletLayer);
        
        // Parse fiber metadata jika ada
        let isFiber = false;
        let cableType = 'Underground';
        let coreCount = '48';
        let status = 'Installed';
        let userDesc = '';
        
        if (layer.description && layer.description.includes('🌐 Fiber:')) {
          isFiber = true;
          const match = layer.description.match(/🌐 Fiber: (\d+)C \| (\w+) \| (\w+)(?:\s*\|\s*(.+))?/);
          if (match) {
            coreCount = match[1];
            cableType = match[2];
            status = match[3];
            userDesc = match[4] || '';
          }
        } else {
          userDesc = layer.description || '';
        }
        
        console.log('🌐 Fiber detected:', isFiber);
        console.log('📋 Fiber details:', { cableType, coreCount, status, userDesc });
        
        // Build form berdasarkan type layer
        let formHtml = '';
        
        if (layer.type === 'polyline' || layer.type === 'polygon' || layer.type === 'rectangle') {
          // Check if Geoman is available
          if (!leafletLayer.pm) {
            console.error('❌ Geoman (pm) not available on this layer!');
            Swal.fire('Error', 'Geoman edit tool tidak tersedia. Pastikan Leaflet Geoman sudah terload.', 'error');
            return;
          }
          
          // Enable visual editing
          console.log('🔧 Enabling Geoman edit mode...');
          leafletLayer.pm.enable({
            allowSelfIntersection: false,
          });
          console.log('✅ Geoman edit mode enabled');
          
          // Zoom to layer
          if (leafletLayer.getBounds) {
            console.log('🔍 Zooming to layer bounds...');
            map.fitBounds(leafletLayer.getBounds());
          }
          
          // Form untuk polyline/polygon
          formHtml = `
            <div style="text-align: left;">
              <label class="form-label">Nama Layer:</label>
              <input type="text" id="edit-layer-name" class="form-control mb-2" value="${layer.name || ''}" placeholder="Nama layer">
              
              <label class="form-label">Deskripsi:</label>
              <textarea id="edit-layer-desc" class="form-control mb-2" rows="2" placeholder="Deskripsi">${userDesc}</textarea>
          `;
          
          // Tambah fiber options untuk polyline
          if (layer.type === 'polyline') {
            console.log('🌐 Building fiber options form...');
            formHtml += `
              <div class="form-check mb-3" style="background: #f8f9fa; padding: 10px; border-radius: 6px;">
                <input type="checkbox" id="edit-is-fiber" class="form-check-input" ${isFiber ? 'checked' : ''} onchange="toggleEditFiberOptions()">
                <label class="form-check-label" for="edit-is-fiber" style="font-weight: 600; color: #1976d2;">
                  🌐 Fiber Optic Cable
                </label>
              </div>
              
              <div id="edit-fiber-options" style="display: ${isFiber ? 'block' : 'none'}; border: 2px solid #1976d2; padding: 15px; border-radius: 8px; background: #e3f2fd; margin-bottom: 15px;">
                <div class="mb-3">
                  <label class="form-label" style="font-weight: 600; font-size: 13px; color: #333;">Cable Type:</label>
                  <select id="edit-cable-type" class="form-select" style="font-size: 14px;">
                    <option value="Aerial" ${cableType === 'Aerial' ? 'selected' : ''}>☁️ Aerial (Udara)</option>
                    <option value="Underground" ${cableType === 'Underground' ? 'selected' : ''}>⛏️ Underground (Tanah)</option>
                    <option value="Duct" ${cableType === 'Duct' ? 'selected' : ''}>🚇 Duct (Pipa)</option>
                    <option value="Underwater" ${cableType === 'Underwater' ? 'selected' : ''}>🌊 Underwater (Air)</option>
                  </select>
                </div>
                
                <div class="mb-3">
                  <label class="form-label" style="font-weight: 600; font-size: 13px; color: #333;">Core Count:</label>
                  <select id="edit-core-count" class="form-select" style="font-size: 14px;">
                    ${[1,2,3,4,5,6,7,8,9,10,11,12,24,48,96,144,288].map(c => 
                      `<option value="${c}" ${coreCount == c ? 'selected' : ''}>${c} Core</option>`
                    ).join('')}
                  </select>
                </div>
                
                <div class="mb-2">
                  <label class="form-label" style="font-weight: 600; font-size: 13px; color: #333;">Status:</label>
                  <select id="edit-cable-status" class="form-select" style="font-size: 14px;" onchange="updateEditColorFromStatus()">
                    <option value="Planned" ${status === 'Planned' ? 'selected' : ''}>🔵 Planned</option>
                    <option value="Installed" ${status === 'Installed' ? 'selected' : ''}>🟢 Installed</option>
                    <option value="Active" ${status === 'Active' ? 'selected' : ''}>✅ Active</option>
                    <option value="Damaged" ${status === 'Damaged' ? 'selected' : ''}>🔴 Damaged</option>
                    <option value="Reserved" ${status === 'Reserved' ? 'selected' : ''}>🟡 Reserved</option>
                  </select>
                </div>
              </div>
            `;
            console.log('✅ Fiber options form built');
          }
          
          formHtml += `
              <label class="form-label">Warna:</label>
              <input type="color" id="edit-layer-color" class="form-control mb-3" value="${layer.color || '#3388ff'}" style="height: 50px;">
            </div>
          `;
          
          console.log('✅ Full form HTML generated, length:', formHtml.length);
          
        } else if (layer.type === 'marker') {
          // Form untuk marker
          const currentIcon = layer.icon || 'pin';
          formHtml = `
            <div style="text-align: left;">
              <label class="form-label">Nama Marker:</label>
              <input type="text" id="edit-layer-name" class="form-control mb-2" value="${layer.name || ''}" placeholder="Nama lokasi">
              
              <label class="form-label">Deskripsi:</label>
              <textarea id="edit-layer-desc" class="form-control mb-2" rows="2" placeholder="Deskripsi">${layer.description || ''}</textarea>
              
              <label class="form-label">Pilih Icon Marker:</label>
              <div class="icon-selector mb-3">
                <div class="icon-option ${currentIcon === 'pin' ? 'selected' : ''}" data-icon="pin" onclick="selectEditMarkerIcon('pin')">
                  <i class="fas fa-map-marker-alt"></i>
                </div>
                <div class="icon-option ${currentIcon === 'circle' ? 'selected' : ''}" data-icon="circle" onclick="selectEditMarkerIcon('circle')">
                  <i class="fas fa-circle"></i>
                </div>
                <div class="icon-option ${currentIcon === 'star' ? 'selected' : ''}" data-icon="star" onclick="selectEditMarkerIcon('star')">
                  <i class="fas fa-star"></i>
                </div>
                <div class="icon-option ${currentIcon === 'flag' ? 'selected' : ''}" data-icon="flag" onclick="selectEditMarkerIcon('flag')">
                  <i class="fas fa-flag"></i>
                </div>
                <div class="icon-option ${currentIcon === 'home' ? 'selected' : ''}" data-icon="home" onclick="selectEditMarkerIcon('home')">
                  <i class="fas fa-home"></i>
                </div>
                <div class="icon-option ${currentIcon === 'building' ? 'selected' : ''}" data-icon="building" onclick="selectEditMarkerIcon('building')">
                  <i class="fas fa-building"></i>
                </div>
                <div class="icon-option ${currentIcon === 'splice' ? 'selected' : ''}" data-icon="splice" onclick="selectEditMarkerIcon('splice')" title="Splice/Joint Closure">
                  <i class="fas fa-link"></i>
                </div>
                <div class="icon-option ${currentIcon === 'splitter' ? 'selected' : ''}" data-icon="splitter" onclick="selectEditMarkerIcon('splitter')" title="Optical Splitter">
                  <i class="fas fa-code-branch"></i>
                </div>
                <div class="icon-option ${currentIcon === 'manhole' ? 'selected' : ''}" data-icon="manhole" onclick="selectEditMarkerIcon('manhole')" title="Manhole">
                  <i class="fas fa-circle"></i>
                </div>
                <div class="icon-option ${currentIcon === 'pole' ? 'selected' : ''}" data-icon="pole" onclick="selectEditMarkerIcon('pole')" title="Pole/Tiang">
                  <i class="fas fa-grip-lines-vertical"></i>
                </div>
              </div>
              
              <label class="form-label">Warna:</label>
              <input type="color" id="edit-layer-color" class="form-control mb-2" value="${layer.color || '#3388ff'}" style="height: 50px;">
            </div>
          `;
        }
        
        console.log('🎨 Opening SweetAlert dialog...');
        console.log('📝 Form HTML length:', formHtml.length);
        
        Swal.fire({
          title: `Edit ${layer.type === 'marker' ? 'Marker' : layer.type === 'polyline' ? 'Polyline' : layer.type === 'polygon' ? 'Polygon' : 'Rectangle'}`,
          html: formHtml,
          width: '400px',
          position: 'top-end',
          backdrop: false, // CRITICAL: No backdrop overlay so map is clickable!
          showCancelButton: true,
          confirmButtonText: '<i class="fas fa-save"></i> Simpan',
          cancelButtonText: '<i class="fas fa-times"></i> Batal',
          allowOutsideClick: true, // Allow clicking on map
          allowEscapeKey: true,
          customClass: {
            confirmButton: 'btn btn-success btn-sm',
            cancelButton: 'btn btn-secondary btn-sm',
            popup: 'swal-edit-popup',
            container: 'swal-edit-container'
          },
          onOpen: () => { // Gunakan onOpen bukan didOpen untuk kompatibilitas
            console.log('✅ SweetAlert dialog opened');
            
            // Set selected icon untuk marker
            if (layer.type === 'marker') {
              window.selectedEditIcon = layer.icon || 'pin';
            }
            
            // Debug: cek apakah form elements ada
            const checkbox = document.getElementById('edit-is-fiber');
            const options = document.getElementById('edit-fiber-options');
            console.log('📋 Fiber checkbox:', checkbox ? 'FOUND' : 'NOT FOUND');
            console.log('📋 Fiber options:', options ? 'FOUND (' + options.style.display + ')' : 'NOT FOUND');
            console.log('🎨 Color preserved:', layer.color);
          },
          preConfirm: () => {
            const name = document.getElementById('edit-layer-name').value;
            const color = document.getElementById('edit-layer-color').value;
            let description = document.getElementById('edit-layer-desc')?.value || '';
            
            // Get icon for marker
            const icon = (layer.type === 'marker') ? (window.selectedEditIcon || layer.icon || 'pin') : layer.icon;
            
            // Handle fiber metadata untuk polyline
            if (layer.type === 'polyline') {
              const isFiberChecked = document.getElementById('edit-is-fiber')?.checked || false;
              if (isFiberChecked) {
                const editCableType = document.getElementById('edit-cable-type')?.value || 'Underground';
                const editCoreCount = document.getElementById('edit-core-count')?.value || '48';
                const editStatus = document.getElementById('edit-cable-status')?.value || 'Installed';
                
                const fiberInfo = `🌐 Fiber: ${editCoreCount}C | ${editCableType} | ${editStatus}`;
                description = description ? `${fiberInfo} | ${description}` : fiberInfo;
              }
            }
            
            // Get updated coordinates dari Geoman jika layer sedang di-edit
            let newCoordinates = layer.coordinates;
            console.log('📊 Original coordinates:', layer.coordinates);
            console.log('🔧 Geoman enabled?', leafletLayer.pm.enabled());
            
            if (leafletLayer.pm.enabled()) {
              if (layer.type === 'polyline') {
                newCoordinates = leafletLayer.getLatLngs().map(ll => [ll.lat, ll.lng]);
                console.log('🆕 New polyline coordinates:', newCoordinates);
              } else if (layer.type === 'polygon' || layer.type === 'rectangle') {
                newCoordinates = leafletLayer.getLatLngs()[0].map(ll => [ll.lat, ll.lng]);
                console.log('🆕 New polygon coordinates:', newCoordinates);
              }
            }
            
            console.log('✅ Returning data:', { name, description, color, icon, coordinates: newCoordinates });
            return { name, description, color, icon, coordinates: newCoordinates };
          }
        }).then((result) => {
          // Disable edit mode
          if (leafletLayer.pm.enabled()) {
            leafletLayer.pm.disable();
          }
          
          if (result.isConfirmed) {
            const { name, description, color, icon, coordinates } = result.value;
            
            console.log('💾 Saving to backend...');
            console.log('📝 Name:', name);
            console.log('📝 Description:', description);
            console.log('🎨 Color:', color);
            console.log('🎯 Icon:', icon);
            console.log('📍 Coordinates:', coordinates);
            console.log('📍 Coordinates JSON:', JSON.stringify(coordinates));
            
            // Update layer
            $.ajax({
              url: `/map/layers/${layerId}`,
              method: 'PATCH',
              headers: { 'X-CSRF-TOKEN': window.csrfToken },
              data: {
                name: name,
                description: description,
                color: color,
                icon: icon,
                coordinates: JSON.stringify(coordinates)
              },
              success: function(res) {
                console.log('✅ Backend response:', res);
                Swal.fire({
                  icon: 'success',
                  title: 'Berhasil!',
                  text: 'Layer berhasil diupdate',
                  timer: 1500,
                  showConfirmButton: false
                });
                
                // Update marker icon if marker type
                if (layer.type === 'marker' && icon) {
                  const newIcon = createMarkerIcon(icon, color);
                  leafletLayer.setIcon(newIcon);
                }
                
                // Update style
                if (leafletLayer.setStyle) {
                  leafletLayer.setStyle({ color: color });
                }
                
                // Reload layers untuk update badge jika fiber
                loadSavedLayers();
              },
              error: function(xhr) {
                console.error('❌ Backend error:', xhr);
                Swal.fire({
                  icon: 'error',
                  title: 'Gagal!',
                  text: 'Gagal update layer: ' + (xhr.responseJSON?.message || xhr.responseText),
                  confirmButtonText: 'OK'
                });
              }
            });
          }
        });
      }
      
      // Toggle fiber options saat edit
      window.toggleEditFiberOptions = function() {
        const checkbox = document.getElementById('edit-is-fiber');
        const options = document.getElementById('edit-fiber-options');
        if (checkbox && options) {
          options.style.display = checkbox.checked ? 'block' : 'none';
          if (checkbox.checked) {
            updateEditColorFromStatus();
          }
        }
      };
      
      // Function untuk select icon saat edit marker
      window.selectEditMarkerIcon = function(iconType) {
        window.selectedEditIcon = iconType;
        
        // Update selected state di UI
        document.querySelectorAll('.icon-option').forEach(opt => {
          opt.classList.remove('selected');
        });
        document.querySelector(`.icon-option[data-icon="${iconType}"]`)?.classList.add('selected');
      };
      
      // Update warna saat edit berdasarkan status
      window.updateEditColorFromStatus = function() {
        const statusSelect = document.getElementById('edit-cable-status');
        const colorInput = document.getElementById('edit-layer-color');
        if (!statusSelect || !colorInput) return;
        
        const statusColors = {
          'Planned': '#3b82f6',
          'Installed': '#22c55e',
          'Active': '#10b981',
          'Damaged': '#ef4444',
          'Reserved': '#f59e0b'
        };
        
        const status = statusSelect.value;
        colorInput.value = statusColors[status] || '#3388ff';
      };

      // Patch updateLayerList agar pakai renderLayerListGrouped
      window.updateLayerList = renderLayerListGrouped;
      </script>
      <script>
      // Filter layer list by name
      function filterLayerList() {
        const q = (document.getElementById('layerSearchInput')?.value || '').toLowerCase();
        const items = document.querySelectorAll('#layerListBody .layer-item');
        items.forEach(item => {
          const name = item.querySelector('.layer-item-name')?.textContent?.toLowerCase() || '';
          if (name.includes(q)) {
            item.style.display = '';
          } else {
            item.style.display = 'none';
          }
        });
      }
      </script>
      </div>
    </div>
    
    <!-- Map Container -->
    <div class="map-container">
      <div id="map" style="height: 100%; width: 100%;"></div>
    </div>
  </div>
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
<script src="https://unpkg.com/leaflet-geometryutil@0.9.3/src/leaflet.geometryutil.js"></script>
<script src="https://unpkg.com/jszip@3.10.1/dist/jszip.min.js"></script>
<script src="/js/togeojson.umd.js"></script>
<link href="https://unpkg.com/@geoman-io/leaflet-geoman-free@2.13.0/dist/leaflet-geoman.css" rel="stylesheet">
<script src="https://unpkg.com/@geoman-io/leaflet-geoman-free@2.13.0/dist/leaflet-geoman.min.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet-search/dist/leaflet-search.min.css" />
<script src="https://unpkg.com/leaflet-search/dist/leaflet-search.min.js"></script>

<script>
  // ======================
  // VERSION INFO
  // ======================
  console.log('%c🗺️ MAP FIBER OPTIC SYSTEM', 'background: #1976d2; color: white; font-size: 16px; padding: 4px 8px; border-radius: 4px;');
  console.log('%c✅ Edit Function Version: 2.1 (Feb 3, 2026)', 'color: #22c55e; font-weight: bold;');
  console.log('%c🌐 Features: Visual Coordinate Editing + Fiber Metadata Dropdowns', 'color: #1976d2;');
  console.log('================================================');
  
  const defaultLatLng = "{{ env('COORDINATE_CENTER', '-6.200000,106.816666') }}".split(',');
  const lat = parseFloat(defaultLatLng[0]);
  const lng = parseFloat(defaultLatLng[1]);

  const map = L.map('map', {
    preferCanvas: true,           // PENTING: Gunakan Canvas renderer (lebih cepat dari SVG)
    renderer: L.canvas(),
    zoomAnimation: true,
    markerZoomAnimation: false,   // Disable marker zoom animation
    fadeAnimation: false
  }).setView([lat, lng], 13);
  



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
    drawMarker: true, // aktifkan marker
    drawPolyline: true, // aktifkan menggambar garis
    drawPolygon: true,
    drawRectangle: true,
    drawText: false, // Text via custom button
    editMode: true,
    dragMode: false,
    cutPolygon: false,
    removalMode: true
  });
  
  // Custom Text/Label Tool Button
  const TextControl = L.Control.extend({
    options: { position: 'topleft' },
    onAdd: function(map) {
      const container = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
      const button = L.DomUtil.create('a', 'leaflet-pm-icon-text', container);
      button.href = '#';
      button.title = 'Tambah Text/Label';
      button.innerHTML = '<i class="fas fa-font" style="font-size: 16px; margin-top: 4px;"></i>';
      
      button.onclick = function(e) {
        e.preventDefault();
        e.stopPropagation();
        activateTextMode();
      };
      
      return container;
    }
  });
  map.addControl(new TextControl());
  
  let textModeActive = false;
  let textModeHandler = null;
  
  function activateTextMode() {
    if (textModeActive) {
      deactivateTextMode();
      return;
    }
    
    textModeActive = true;
    map.getContainer().style.cursor = 'crosshair';
    
    Swal.fire({
      icon: 'info',
      title: 'Mode Text Aktif',
      text: 'Klik di peta untuk menambahkan text/label. Tekan ESC untuk membatalkan.',
      timer: 3000,
      showConfirmButton: false
    });
    
    textModeHandler = function(e) {
      map.off('click', textModeHandler);
      deactivateTextMode();
      showTextInputDialog(e.latlng);
    };
    
    map.on('click', textModeHandler);
    
    // ESC untuk cancel
    document.addEventListener('keydown', function escHandler(e) {
      if (e.key === 'Escape') {
        deactivateTextMode();
        document.removeEventListener('keydown', escHandler);
      }
    });
  }
  
  function deactivateTextMode() {
    textModeActive = false;
    map.getContainer().style.cursor = '';
    if (textModeHandler) {
      map.off('click', textModeHandler);
      textModeHandler = null;
    }
  }
  
  function showTextInputDialog(latlng) {
    Swal.fire({
      title: '📝 Tambah Text/Label',
      html: `
        <div style="text-align: left;">
          <label class="form-label">Text/Label:</label>
          <input type="text" id="swal-text-content" class="form-control mb-2" placeholder="Masukkan text..." autofocus>
          
          <label class="form-label">Font Size:</label>
          <input type="number" id="swal-text-size" class="form-control mb-2" value="14" min="8" max="72">
          
          <label class="form-label">Warna Text:</label>
          <input type="color" id="swal-text-color" class="form-control mb-2" value="#000000">
          
          <label class="form-label">Background:</label>
          <div class="form-check">
            <input type="checkbox" id="swal-text-bg" class="form-check-input" checked>
            <label class="form-check-label" for="swal-text-bg">Gunakan background putih</label>
          </div>
        </div>
      `,
      showCancelButton: true,
      confirmButtonText: 'Simpan',
      cancelButtonText: 'Batal',
      preConfirm: () => {
        const text = document.getElementById('swal-text-content').value.trim();
        const size = parseInt(document.getElementById('swal-text-size').value) || 14;
        const color = document.getElementById('swal-text-color').value;
        const useBg = document.getElementById('swal-text-bg').checked;
        
        if (!text) {
          Swal.showValidationMessage('Text tidak boleh kosong');
          return false;
        }
        
        return { text, size, color, useBg };
      }
    }).then((result) => {
      if (result.isConfirmed) {
        const { text, size, color, useBg } = result.value;
        createTextMarker(latlng, text, size, color, useBg);
      }
    });
  }
  
  function createTextMarker(latlng, text, fontSize, color, useBg) {
    const bgStyle = useBg ? 'background: white; padding: 4px 8px; border-radius: 4px; box-shadow: 0 2px 6px rgba(0,0,0,0.3);' : '';
    
    const textMarker = L.marker(latlng, {
      icon: L.divIcon({
        html: `<div style="color: ${color}; font-size: ${fontSize}px; font-weight: bold; white-space: nowrap; ${bgStyle}">${text}</div>`,
        className: 'text-label-marker',
        iconSize: [null, null],
        iconAnchor: [0, 0]
      })
    });
    
    // Simpan data text untuk backend
    const textData = {
      text: text,
      fontSize: fontSize,
      color: color,
      useBg: useBg
    };
    
    // Show save dialog
    const popup = L.popup()
      .setLatLng(latlng)
      .setContent(`
        <div style="min-width: 200px;">
          <strong>📝 "${text}"</strong><br><br>
          <button class="btn btn-sm btn-success btn-block" onclick="saveTextLayer([${latlng.lat}, ${latlng.lng}], '${text.replace(/'/g, "\\'")}', ${fontSize}, '${color}', ${useBg})">
            <i class="fas fa-save"></i> Simpan Text
          </button>
          <button class="btn btn-sm btn-danger btn-block" onclick="cancelTextLayer()">
            <i class="fas fa-times"></i> Batal
          </button>
        </div>
      `)
      .openOn(map);
    
    textMarker.addTo(map);
    window.tempLayer = textMarker;
    window.tempTextData = textData;
  }
  
  window.saveTextLayer = function(coords, text, fontSize, color, useBg) {
    const description = `Font: ${fontSize}px, Color: ${color}, Background: ${useBg ? 'Ya' : 'Tidak'}`;
    
    console.log('📝 Saving text layer:', { coords, text, fontSize, color, useBg });
    
    $.ajax({
      url: '/map/layers',
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
      data: {
        name: text,
        description: description,
        type: 'marker', // Simpan sebagai marker dengan icon='text'
        coordinates: JSON.stringify([coords]),
        color: color,
        weight: fontSize, // Gunakan weight untuk simpan fontSize
        opacity: useBg ? 1 : 0, // Gunakan opacity untuk simpan useBg flag
        icon: 'text', // Icon 'text' sebagai penanda ini text label
        distance: null,
        area: null
      },
      success: function(response) {
        Swal.fire({
          icon: 'success',
          title: 'Berhasil!',
          text: 'Text label berhasil disimpan',
          timer: 2000,
          showConfirmButton: false
        });
        
        map.closePopup();
        
        if (window.tempLayer) {
          // Store reference
          window.layerObjects[response.data.id] = window.tempLayer;
          window.tempLayer = null;
        }
        
        loadSavedLayers();
      },
      error: function(xhr) {
        console.error('❌ Save failed:', xhr.status, xhr.responseText);
        console.error('Response JSON:', xhr.responseJSON);
        
        let errorMsg = 'Terjadi kesalahan saat menyimpan text';
        
        if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMsg = xhr.responseJSON.message;
        }
        
        if (xhr.responseJSON && xhr.responseJSON.errors) {
          errorMsg += '\n\nDetail:\n' + Object.entries(xhr.responseJSON.errors)
            .map(([field, msgs]) => `${field}: ${msgs.join(', ')}`)
            .join('\n');
        }
        
        Swal.fire({
          icon: 'error',
          title: 'Gagal Menyimpan',
          text: errorMsg,
          confirmButtonText: 'OK'
        });
      }
    });
  };
  
  window.cancelTextLayer = function() {
    if (window.tempLayer) {
      map.removeLayer(window.tempLayer);
      window.tempLayer = null;
    }
    window.tempTextData = null;
    map.closePopup();
  };
  
  // Event handler untuk Edit mode Geoman
  map.on('pm:edit', function(e) {
    const layers = e.layers;
    
    layers.eachLayer(function(layer) {
      // Cari layer ID dari window.layerObjects
      let layerId = null;
      for (const [id, leafletLayer] of Object.entries(window.layerObjects)) {
        if (leafletLayer === layer) {
          layerId = parseInt(id);
          break;
        }
      }
      
      if (!layerId) {
        console.warn('Layer edited but ID not found');
        return;
      }
      
      console.log('📝 Layer edited:', layerId);
      
      // Get updated coordinates
      let coordinates = [];
      let type = '';
      
      if (layer.getLatLng) {
        // Marker
        const latlng = layer.getLatLng();
        coordinates = [[latlng.lat, latlng.lng]];
        type = 'marker';
      } else if (layer.getLatLngs) {
        // Polyline/Polygon/Rectangle
        const latlngs = layer.getLatLngs();
        if (Array.isArray(latlngs[0])) {
          // Polygon/Rectangle
          coordinates = latlngs[0].map(ll => [ll.lat, ll.lng]);
          type = layer.options.shape || 'polygon';
        } else {
          // Polyline
          coordinates = latlngs.map(ll => [ll.lat, ll.lng]);
          type = 'polyline';
        }
      }
      
      // Update di backend
      $.ajax({
        url: '/map/layers/' + layerId,
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': window.csrfToken },
        data: {
          coordinates: JSON.stringify(coordinates)
        },
        success: function(res) {
          console.log('✅ Layer coordinates updated:', res);
          
          // Update di savedLayersData
          const idx = (window.savedLayersData || []).findIndex(l => l.id === layerId);
          if (idx >= 0) {
            window.savedLayersData[idx].coordinates = JSON.stringify(coordinates);
          }
          
          // Show success notification
          Swal.fire({
            icon: 'success',
            title: 'Tersimpan!',
            text: 'Perubahan layer berhasil disimpan',
            timer: 1500,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
          });
        },
        error: function(xhr) {
          console.error('❌ Failed to update layer:', xhr.responseText);
          Swal.fire({
            icon: 'error',
            title: 'Gagal Update',
            text: 'Gagal menyimpan perubahan: ' + xhr.responseText,
            confirmButtonText: 'OK'
          });
        }
      });
    });
  });

  map.on('pm:create', e => {
    if (e.shape === 'Line') {
      const latlngs = e.layer.getLatLngs();
      let totalDistance = 0;

      for (let i = 1; i < latlngs.length; i++) {
        totalDistance += latlngs[i - 1].distanceTo(latlngs[i]);
      }

      const distanceInMeters = totalDistance.toFixed(2);
      
      window.tempLayer = e.layer;
      window.selectedColor = '#22c55e'; // Default hijau untuk fiber installed
      
      const formHtml = `
        <div style="text-align: left;">
          <div class="alert alert-info mb-3" style="font-size: 13px; padding: 8px;">
            📏 <strong>Jarak:</strong> ${distanceInMeters} m
          </div>
          
          <label class="form-label">Nama Layer:</label>
          <input type="text" id="layer-name" class="form-control mb-2" placeholder="Nama layer (opsional)">
          
          <label class="form-label">Deskripsi:</label>
          <textarea id="layer-desc" class="form-control mb-2" rows="2" placeholder="Deskripsi/catatan"></textarea>
          
          <div class="form-check mb-3" style="background: #f8f9fa; padding: 10px; border-radius: 6px;">
            <input type="checkbox" id="is-fiber-cable" class="form-check-input" onchange="toggleFiberOptions()">
            <label class="form-check-label" for="is-fiber-cable" style="font-weight: 600; color: #1976d2;">
              🌐 Fiber Optic Cable
            </label>
          </div>
          
          <div id="fiber-options" style="display:none; border: 2px solid #1976d2; padding: 15px; border-radius: 8px; background: #e3f2fd; margin-bottom: 15px;">
            <div class="mb-3">
              <label class="form-label" style="font-weight: 600; font-size: 13px; color: #333;">Cable Type:</label>
              <select id="cable-type" class="form-select" style="font-size: 14px;">
                <option value="Aerial">☁️ Aerial (Udara)</option>
                <option value="Underground" selected>⛏️ Underground (Tanah)</option>
                <option value="Duct">🚇 Duct (Pipa)</option>
                <option value="Underwater">🌊 Underwater (Air)</option>
              </select>
            </div>
            
            <div class="mb-3">
              <label class="form-label" style="font-weight: 600; font-size: 13px; color: #333;">Core Count:</label>
              <select id="core-count" class="form-select" style="font-size: 14px;">
                ${[1,2,3,4,5,6,7,8,9,10,11,12,24,48,96,144,288].map(c => 
                  `<option value="${c}" ${c === 48 ? 'selected' : ''}>${c} Core</option>`
                ).join('')}
              </select>
            </div>
            
            <div class="mb-2">
              <label class="form-label" style="font-weight: 600; font-size: 13px; color: #333;">Status:</label>
              <select id="cable-status" class="form-select" style="font-size: 14px;" onchange="updateColorFromStatus()">
                <option value="Planned">🔵 Planned</option>
                <option value="Installed" selected>🟢 Installed</option>
                <option value="Active">✅ Active</option>
                <option value="Damaged">🔴 Damaged</option>
                <option value="Reserved">🟡 Reserved</option>
              </select>
            </div>
          </div>
          
          <label class="form-label">Warna:</label>
          <input type="color" id="layer-color" class="form-control mb-3" value="#22c55e" style="height: 50px;">
        </div>
      `;
      
      Swal.fire({
        title: 'Buat Polyline Baru',
        html: formHtml,
        width: '400px',
        position: 'top-end',
        backdrop: false,
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-save"></i> Simpan',
        cancelButtonText: '<i class="fas fa-times"></i> Batal',
        customClass: {
          confirmButton: 'btn btn-success btn-sm',
          cancelButton: 'btn btn-secondary btn-sm',
          popup: 'swal-edit-popup',
          container: 'swal-edit-container'
        },
        preConfirm: () => {
          const name = document.getElementById('layer-name').value.trim();
          const desc = document.getElementById('layer-desc').value.trim();
          const color = document.getElementById('layer-color').value;
          const isFiber = document.getElementById('is-fiber-cable').checked;
          
          let description = desc;
          if (isFiber) {
            const cableType = document.getElementById('cable-type').value;
            const coreCount = document.getElementById('core-count').value;
            const status = document.getElementById('cable-status').value;
            const fiberInfo = `🌐 Fiber: ${coreCount}C | ${cableType} | ${status}`;
            description = desc ? `${fiberInfo} | ${desc}` : fiberInfo;
          }
          
          return { name, description, color, coordinates: latlngs.map(ll => [ll.lat, ll.lng]) };
        }
      }).then((result) => {
        if (result.isConfirmed) {
          const { name, description, color, coordinates } = result.value;
          saveDrawnLayer(coordinates, 'polyline', distanceInMeters, null, name, description, color);
        } else {
          cancelLayer();
        }
      })
    } else if (e.shape === 'Polygon' || e.shape === 'Rectangle') {
      const latlngs = e.layer.getLatLngs()[0];
      
      // Hitung area menggunakan fungsi custom (lebih reliable dari L.GeometryUtil)
      const area = calculatePolygonArea(latlngs);
      
      const popup = L.popup()
        .setLatLng(latlngs[0])
        .setContent(`
          <div style="min-width: 280px;">
            <strong>📐 Luas: ${area.toFixed(2)} m²</strong><br><br>
            <input type="text" id="layer-name" class="form-control form-control-sm mb-2" placeholder="Nama layer">
            <textarea id="layer-desc" class="form-control form-control-sm mb-2" rows="2" placeholder="Deskripsi"></textarea>
            
            <label class="small mb-1">Pilih Warna:</label>
            <div class="mb-2" style="display: flex; gap: 5px; flex-wrap: wrap;">
              <label style="width:30px; height:30px; background:#FF0000; border:2px solid #ddd; border-radius:4px; cursor:pointer;" onclick="selectColor('#FF0000')"></label>
              <label style="width:30px; height:30px; background:#00FF00; border:2px solid #ddd; border-radius:4px; cursor:pointer;" onclick="selectColor('#00FF00')"></label>
              <label style="width:30px; height:30px; background:#0000FF; border:2px solid #ddd; border-radius:4px; cursor:pointer;" onclick="selectColor('#0000FF')"></label>
              <label style="width:30px; height:30px; background:#FFFF00; border:2px solid #ddd; border-radius:4px; cursor:pointer;" onclick="selectColor('#FFFF00')"></label>
              <label style="width:30px; height:30px; background:#FF00FF; border:2px solid #ddd; border-radius:4px; cursor:pointer;" onclick="selectColor('#FF00FF')"></label>
              <label style="width:30px; height:30px; background:#00FFFF; border:2px solid #ddd; border-radius:4px; cursor:pointer;" onclick="selectColor('#00FFFF')"></label>
              <label style="width:30px; height:30px; background:#FFA500; border:2px solid #ddd; border-radius:4px; cursor:pointer;" onclick="selectColor('#FFA500')"></label>
              <label style="width:30px; height:30px; background:#3388ff; border:2px solid #000; border-radius:4px; cursor:pointer;" onclick="selectColor('#3388ff')"></label>
            </div>
            
            <button class="btn btn-sm btn-success btn-block" onclick="saveDrawnLayer(${JSON.stringify(latlngs.map(ll => [ll.lat, ll.lng]))}, '${e.shape.toLowerCase()}', null, ${area})">
              <i class="fas fa-save"></i> Simpan Layer
            </button>
            <button class="btn btn-sm btn-danger btn-block" onclick="cancelLayer()">
              <i class="fas fa-times"></i> Batal
            </button>
          </div>
        `)
        .openOn(map);
      
      window.tempLayer = e.layer;
      window.selectedColor = '#3388ff';
    } else if (e.shape === 'Marker') {
      const latlng = e.layer.getLatLng();
      
      const popup = L.popup()
        .setLatLng(latlng)
        .setContent(`
          <div style="min-width: 280px;">
            <strong>📍 Marker Baru</strong><br><br>
            <input type="text" id="layer-name" class="form-control form-control-sm mb-2" placeholder="Nama lokasi" required>
            <textarea id="layer-desc" class="form-control form-control-sm mb-2" rows="2" placeholder="Deskripsi/alamat"></textarea>
            
            <label class="small mb-1">Pilih Icon Marker:</label>
            <div class="icon-selector mb-2">
              <div class="icon-option selected" data-icon="pin" onclick="selectMarkerIcon('pin')">
                <i class="fas fa-map-marker-alt"></i>
              </div>
              <div class="icon-option" data-icon="circle" onclick="selectMarkerIcon('circle')">
                <i class="fas fa-circle"></i>
              </div>
              <div class="icon-option" data-icon="star" onclick="selectMarkerIcon('star')">
                <i class="fas fa-star"></i>
              </div>
              <div class="icon-option" data-icon="flag" onclick="selectMarkerIcon('flag')">
                <i class="fas fa-flag"></i>
              </div>
              <div class="icon-option" data-icon="home" onclick="selectMarkerIcon('home')">
                <i class="fas fa-home"></i>
              </div>
              <div class="icon-option" data-icon="building" onclick="selectMarkerIcon('building')">
                <i class="fas fa-building"></i>
              </div>
              <div class="icon-option" data-icon="splice" onclick="selectMarkerIcon('splice')" title="Splice/Joint Closure">
                <i class="fas fa-link"></i>
              </div>
              <div class="icon-option" data-icon="splitter" onclick="selectMarkerIcon('splitter')" title="Optical Splitter">
                <i class="fas fa-code-branch"></i>
              </div>
              <div class="icon-option" data-icon="manhole" onclick="selectMarkerIcon('manhole')" title="Manhole">
                <i class="fas fa-circle"></i>
              </div>
              <div class="icon-option" data-icon="pole" onclick="selectMarkerIcon('pole')" title="Pole/Tiang">
                <i class="fas fa-grip-lines-vertical"></i>
              </div>
            </div>
            
            <label class="small mb-1">Pilih Warna Marker:</label>
            <div class="mb-2" style="display: flex; gap: 5px; flex-wrap: wrap;">
              <label style="width:30px; height:30px; background:#FF0000; border:2px solid #ddd; border-radius:50%; cursor:pointer;" onclick="selectMarkerColor('#FF0000')"></label>
              <label style="width:30px; height:30px; background:#00FF00; border:2px solid #ddd; border-radius:50%; cursor:pointer;" onclick="selectMarkerColor('#00FF00')"></label>
              <label style="width:30px; height:30px; background:#0000FF; border:2px solid #ddd; border-radius:50%; cursor:pointer;" onclick="selectMarkerColor('#0000FF')"></label>
              <label style="width:30px; height:30px; background:#FFFF00; border:2px solid #ddd; border-radius:50%; cursor:pointer;" onclick="selectMarkerColor('#FFFF00')"></label>
              <label style="width:30px; height:30px; background:#FF00FF; border:2px solid #ddd; border-radius:50%; cursor:pointer;" onclick="selectMarkerColor('#FF00FF')"></label>
              <label style="width:30px; height:30px; background:#00FFFF; border:2px solid #ddd; border-radius:50%; cursor:pointer;" onclick="selectMarkerColor('#00FFFF')"></label>
              <label style="width:30px; height:30px; background:#FFA500; border:2px solid #ddd; border-radius:50%; cursor:pointer;" onclick="selectMarkerColor('#FFA500')"></label>
              <label style="width:30px; height:30px; background:#3388ff; border:2px solid #000; border-radius:50%; cursor:pointer;" onclick="selectMarkerColor('#3388ff')"></label>
            </div>
            
            <button class="btn btn-sm btn-success btn-block" onclick="saveDrawnLayer([[${latlng.lat}, ${latlng.lng}]], 'marker', null, null)">
              <i class="fas fa-save"></i> Simpan Marker
            </button>
            <button class="btn btn-sm btn-danger btn-block" onclick="cancelLayer()">
              <i class="fas fa-times"></i> Batal
            </button>
          </div>
        `)
        .openOn(map);
      
      window.tempLayer = e.layer;
      window.selectedColor = '#3388ff';
      window.selectedIcon = 'pin'; // Default icon
    }
  });
  
  // Global variables for marker customization
  window.selectedIcon = 'pin';
  
  // Function to select marker icon
  window.selectMarkerIcon = function(iconType) {
    window.selectedIcon = iconType;
    
    // Update selected state in UI
    document.querySelectorAll('.icon-option').forEach(opt => {
      opt.classList.remove('selected');
    });
    document.querySelector(`.icon-option[data-icon="${iconType}"]`).classList.add('selected');
    
    // Update preview if tempLayer exists
    if (window.tempLayer) {
      const color = window.selectedColor || '#3388ff';
      const icon = createMarkerIcon(iconType, color);
      window.tempLayer.setIcon(icon);
    }
  };

  // Fungsi select color
  window.selectColor = function(color) {
    window.selectedColor = color;
    if (window.tempLayer && window.tempLayer.setStyle) {
      window.tempLayer.setStyle({ color: color });
    }
  };
  
  // Function to create marker icon based on type and color
  window.createMarkerIcon = function(iconType, color) {
    let html = '';
    
    switch(iconType) {
      case 'pin':
        html = `
          <svg width="25" height="41" viewBox="0 0 25 41" xmlns="http://www.w3.org/2000/svg">
            <path d="M12.5 0C5.6 0 0 5.6 0 12.5c0 1.9.4 3.7 1.2 5.3L12.5 41l11.3-23.2c.8-1.6 1.2-3.4 1.2-5.3C25 5.6 19.4 0 12.5 0z" fill="${color}" stroke="#fff" stroke-width="2"/>
            <circle cx="12.5" cy="12.5" r="5" fill="#fff"/>
          </svg>
        `;
        break;
      case 'circle':
        html = `<i class="fas fa-circle" style="color: ${color}; font-size: 24px; text-shadow: 0 0 3px white;"></i>`;
        break;
      case 'star':
        html = `<i class="fas fa-star" style="color: ${color}; font-size: 24px; text-shadow: 0 0 3px white;"></i>`;
        break;
      case 'flag':
        html = `<i class="fas fa-flag" style="color: ${color}; font-size: 24px; text-shadow: 0 0 3px white;"></i>`;
        break;
      case 'home':
        html = `<i class="fas fa-home" style="color: ${color}; font-size: 24px; text-shadow: 0 0 3px white;"></i>`;
        break;
      case 'building':
        html = `<i class="fas fa-building" style="color: ${color}; font-size: 24px; text-shadow: 0 0 3px white;"></i>`;
        break;
      case 'splice':
        html = `<div style="background: #1e40af; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.3);"><i class="fas fa-link" style="color: white; font-size: 16px;"></i></div>`;
        break;
      case 'splitter':
        html = `<div style="background: #7c3aed; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.3);"><i class="fas fa-code-branch" style="color: white; font-size: 16px;"></i></div>`;
        break;
      case 'manhole':
        html = `<div style="background: #64748b; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.3);"><i class="fas fa-circle" style="color: white; font-size: 16px;"></i></div>`;
        break;
      case 'pole':
        html = `<div style="background: ${color}; width: 8px; height: 40px; border-radius: 2px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.3); position: relative;">
          <div style="position: absolute; top: 5px; width: 12px; height: 3px; background: rgba(255,255,255,0.8); border-radius: 1px;"></div>
          <div style="position: absolute; bottom: 15px; width: 12px; height: 3px; background: rgba(255,255,255,0.8); border-radius: 1px;"></div>
        </div>`;
        break;
      default:
        html = `<i class="fas fa-map-marker-alt" style="color: ${color}; font-size: 24px; text-shadow: 0 0 3px white;"></i>`;
    }
    
    return L.divIcon({
      html: html,
      className: 'custom-marker',
      iconSize: [30, 30],
      iconAnchor: [15, 30],
      popupAnchor: [0, -30]
    });
  };
  
  window.selectMarkerColor = function(color) {
    window.selectedColor = color;
    if (window.tempLayer) {
      const iconType = window.selectedIcon || 'pin';
      const icon = createMarkerIcon(iconType, color);
      window.tempLayer.setIcon(icon);
    }
  };
  
  // Toggle fiber optic options
  window.toggleFiberOptions = function() {
    const checkbox = document.getElementById('is-fiber-cable');
    const options = document.getElementById('fiber-options');
    if (checkbox && options) {
      options.style.display = checkbox.checked ? 'block' : 'none';
      // Auto-update color jika fiber dicentang
      if (checkbox.checked) {
        updateColorFromStatus();
      }
    }
  };
  
  // Update warna line berdasarkan status kabel fiber
  window.updateColorFromStatus = function() {
    const statusSelect = document.getElementById('cable-status');
    const colorInput = document.getElementById('layer-color');
    if (!statusSelect) return;
    
    const statusColors = {
      'Planned': '#3b82f6',
      'Installed': '#22c55e',
      'Active': '#10b981',
      'Damaged': '#ef4444',
      'Reserved': '#f59e0b'
    };
    
    const status = statusSelect.value;
    const color = statusColors[status] || '#3388ff';
    
    // Update color input if exists (SweetAlert form)
    if (colorInput) {
      colorInput.value = color;
    }
    
    // Also update tempLayer style
    if (window.tempLayer && window.tempLayer.setStyle) {
      window.tempLayer.setStyle({ color: color });
    }
    
    window.selectedColor = color;
  };

  // Fungsi untuk membatalkan layer
  window.cancelLayer = function() {
    if (window.tempLayer) {
      map.removeLayer(window.tempLayer);
      window.tempLayer = null;
    }
    map.closePopup();
  };
  
  // Fungsi custom untuk hitung area polygon (menggantikan L.GeometryUtil.geodesicArea)
  function calculatePolygonArea(latlngs) {
    // Menggunakan formula Shoelace (Gauss area formula) dengan koreksi Earth radius
    const earthRadius = 6371000; // meter
    
    if (!latlngs || latlngs.length < 3) return 0;
    
    let total = 0;
    const numPoints = latlngs.length;
    
    for (let i = 0; i < numPoints; i++) {
      const p1 = latlngs[i];
      const p2 = latlngs[(i + 1) % numPoints];
      
      total += (toRad(p2.lng) - toRad(p1.lng)) * 
               (2 + Math.sin(toRad(p1.lat)) + Math.sin(toRad(p2.lat)));
    }
    
    total = Math.abs(total * earthRadius * earthRadius / 2);
    return total;
  }
  
  function toRad(degrees) {
    return degrees * Math.PI / 180;
  }

  // Fungsi untuk menyimpan layer
  window.saveDrawnLayer = function(coordinates, type, distance, area, nameOverride, descriptionOverride, colorOverride) {
    // Support both old popup-based and new SweetAlert-based calls
    const name = nameOverride || document.getElementById('layer-name')?.value || '';
    let description = descriptionOverride || document.getElementById('layer-desc')?.value || '';
    let color = colorOverride || document.getElementById('layer-color')?.value || window.selectedColor || '#3388ff';
    const icon = window.selectedIcon || 'pin';
    const weight = 3;
    const opacity = 0.6;
    
    // Handle fiber optic metadata for polyline (only if not using SweetAlert override)
    if (type === 'polyline' && !descriptionOverride) {
      const isFiber = document.getElementById('is-fiber-cable')?.checked || false;
      if (isFiber) {
        const cableType = document.getElementById('cable-type')?.value || 'Underground';
        const coreCount = document.getElementById('core-count')?.value || '48';
        const status = document.getElementById('cable-status')?.value || 'Installed';
        
        // Prepend fiber info to description
        const fiberInfo = `🌐 Fiber: ${coreCount}C | ${cableType} | ${status}`;
        description = description ? `${fiberInfo} | ${description}` : fiberInfo;
        
        // Use status color if no manual color selection
        const statusColors = {
          'Planned': '#3b82f6',
          'Installed': '#22c55e',
          'Active': '#10b981',
          'Damaged': '#ef4444',
          'Reserved': '#f59e0b'
        };
        color = statusColors[status] || color;
      }
    }

    $.ajax({
      url: '/map/layers',
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      data: {
        name: name,
        description: description,
        type: type,
        coordinates: JSON.stringify(coordinates),
        distance: distance,
        area: area,
        color: color,
        weight: weight,
        opacity: opacity,
        icon: icon
      },
      success: function(response) {
        Swal.fire({
          icon: 'success',
          title: 'Berhasil!',
          text: 'Layer berhasil disimpan',
          timer: 2000,
          showConfirmButton: false
        });
        map.closePopup();
        
        // Add tempLayer to savedLayersGroup
        if (window.tempLayer) {
          // CRITICAL: Remove from map first (Geoman adds it automatically)
          if (map.hasLayer(window.tempLayer)) {
            map.removeLayer(window.tempLayer);
          }
          
          // Now add to appropriate container based on type
          if (type === 'marker') {
            window.importedMarkerCluster.addLayer(window.tempLayer);
          } else {
            savedLayersGroup.addLayer(window.tempLayer);
          }
          
          // Update popup
          let popupContent = `<strong>${response.data.name || 'Layer #' + response.data.id}</strong><br>`;
          if (response.data.description) popupContent += `${response.data.description}<br>`;
          if (response.data.distance) popupContent += `📏 ${response.data.distance} m<br>`;
          if (response.data.area) popupContent += `📐 ${response.data.area} m²<br>`;
          popupContent += `<small>📅 ${new Date(response.data.created_at).toLocaleDateString('id-ID')}</small><br>`;
          popupContent += `<button class="btn btn-sm btn-danger mt-2" onclick="deleteLayer(${response.data.id})">
            <i class="fas fa-trash"></i> Hapus
          </button>`;
          window.tempLayer.bindPopup(popupContent);
          
          // Store reference
          window.layerObjects[response.data.id] = window.tempLayer;
          window.tempLayer = null;
        }
        
        // Reload list
        loadSavedLayers();
      },
      error: function(xhr) {
        Swal.fire({
          icon: 'error',
          title: 'Gagal Menyimpan',
          text: xhr.responseJSON?.message || 'Terjadi kesalahan saat menyimpan layer',
          confirmButtonText: 'OK'
        });
      }
    });
  };

  // Layer group untuk menyimpan layer yang sudah disimpan
  const savedLayersGroup = L.layerGroup().addTo(map);
  
  // Cluster group khusus marker hasil import dengan optimasi performa
  // disableClusteringAtZoom: 14 = Otomatis tampilkan semua marker individual di zoom >= 14
  window.importedMarkerCluster = L.markerClusterGroup({
    maxClusterRadius: 80,              // Radius clustering
    spiderfyOnMaxZoom: true,
    showCoverageOnHover: false,        // Disable coverage (lebih ringan)
    zoomToBoundsOnClick: true,
    disableClusteringAtZoom: 14,       // PENTING: Disable clustering di zoom >= 14
    animate: false,                    // PENTING: Disable animasi untuk performa
    removeOutsideVisibleBounds: true,  // PENTING: Hapus marker di luar viewport
    chunkedLoading: true,              // Load marker bertahap
    chunkInterval: 200,
    chunkDelay: 50,
    iconCreateFunction: function(cluster) {
      const count = cluster.getChildCount();
      let className = 'marker-cluster-small';
      if (count >= 100) className = 'marker-cluster-large';
      else if (count >= 10) className = 'marker-cluster-medium';
      return L.divIcon({
        html: '<div><span>' + count + '</span></div>',
        className: 'marker-cluster ' + className,
        iconSize: L.point(40, 40)
      });
    }
  });
  map.addLayer(window.importedMarkerCluster);
  
  // Monitoring zoom level untuk debugging
  map.on('zoomend', function() {
    const zoom = map.getZoom();
    console.log('🔍 Current zoom:', zoom);
    if (zoom >= 14) {
      console.log('📍 Zoom ≥ 14 - Showing all markers WITHOUT clustering');
    } else {
      console.log('🗂️ Zoom < 14 - Using clustering mode');
    }
  });

  // Store layers data globally
  window.savedLayersData = [];
  window.layerObjects = {}; // Map layer ID to Leaflet layer object

  window.triggerImportKml = function() {
    const input = document.getElementById('kmlFileInput');
    if (input) {
      input.value = '';
      input.click();
    }
  };

  async function readKmlFromFile(file) {
    console.log('Reading KML file:', file.name); // Debug log
    const fileName = file.name.toLowerCase();
    if (fileName.endsWith('.kmz')) {
        console.log('Detected KMZ file, extracting...'); // Debug log
        try {
            const zip = await JSZip.loadAsync(file);
            console.log('KMZ file loaded successfully'); // Debug log
            const kmlFile = Object.keys(zip.files).find(name => name.toLowerCase().endsWith('.kml'));
            if (!kmlFile) {
                console.error('KMZ does not contain a KML file'); // Debug log
                throw new Error('KMZ tidak berisi file KML');
            }
            console.log('Found KML file inside KMZ:', kmlFile); // Debug log
            const kmlContent = await zip.files[kmlFile].async('text');
            console.log('KML content extracted from KMZ:', kmlContent.slice(0, 100)); // Debug log
            return kmlContent;
        } catch (error) {
            console.error('Error reading KMZ file:', error); // Debug log
            throw new Error('Gagal membaca file KMZ');
        }
    }
    console.log('Detected KML file, reading...'); // Debug log
    try {
        const kmlContent = await file.text();
        console.log('KML content read successfully:', kmlContent.slice(0, 100)); // Debug log
        return kmlContent;
    } catch (error) {
        console.error('Error reading KML file:', error); // Debug log
        throw new Error('Gagal membaca file KML');
    }
}

  function loadScriptOnce(src) {
    return new Promise((resolve, reject) => {
      const existing = Array.from(document.scripts).find(s => s.src === src);
      if (existing) {
        existing.addEventListener('load', () => resolve());
        existing.addEventListener('error', () => reject());
        if (existing.complete) resolve();
        return;
      }
      const script = document.createElement('script');
      script.src = src;
      script.async = true;
      script.onload = () => resolve();
      script.onerror = () => reject();
      document.body.appendChild(script);
    });
  }

  async function ensureToGeoJsonLoaded() {
    // Tidak perlu load dari CDN, cukup cek window.toGeoJSON/togeojson
    if (window.toGeoJSON || window.togeojson) return;
    throw new Error('Pustaka toGeoJSON tidak ditemukan di window. Pastikan <script src="/js/togeojson.umd.js"> sudah dimuat.');
  }

  function computeDistance(coords) {
    let total = 0;
    for (let i = 1; i < coords.length; i++) {
      total += L.latLng(coords[i - 1]).distanceTo(L.latLng(coords[i]));
    }
    return parseFloat(total.toFixed(2));
  }

  function computeArea(coords) {
    const latlngs = coords.map(c => L.latLng(c[0], c[1]));
    const area = L.GeometryUtil.geodesicArea(latlngs);
    return parseFloat(area.toFixed(2));
  }

  function saveImportedLayer(payload) {
    console.log('Sending payload to server:', payload); // Added log to show payload being sent
    return $.ajax({
      url: '/map/layers',
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      data: payload
    }).done(response => {
      console.log('Server response:', response); // Added log to show server response
    }).fail(xhr => {
      console.error('Failed to save layer:', xhr.responseText); // Added log to show error response
    });
  }

  function normalizeGeoJsonFeature(feature) {
    const geometry = feature.geometry;
    if (!geometry) return [];

    const name = feature.properties?.name || 'Imported Layer';
    const description = feature.properties?.description || '';
    const type = geometry.type;
    const coords = geometry.coordinates;
    const results = [];

    // KML: [lon, lat, ...], Leaflet: [lat, lon]
    const toLatLng = (c) => {
      if (!Array.isArray(c) || c.length < 2) return [null, null];
      // KML sometimes gives [lon, lat], sometimes [lon, lat, alt]
      return [parseFloat(c[1]), parseFloat(c[0])];
    };
    const normalizeCoord = (c) => toLatLng(c);
    const normalizeLine = (line) => Array.isArray(line) ? line.map(normalizeCoord) : [];

    const makePayload = (shapeType, latLngCoords) => {
      const color = '#3388ff';
      const weight = 3;
      const opacity = 0.6;
      const icon = 'pin';
      const payload = {
        name,
        description,
        type: shapeType,
        coordinates: JSON.stringify(latLngCoords),
        distance: null,
        area: null,
        color,
        weight,
        opacity,
        icon
      };

      if (shapeType === 'polyline') {
        payload.distance = computeDistance(latLngCoords);
      }
      if (shapeType === 'polygon' || shapeType === 'rectangle') {
        payload.area = computeArea(latLngCoords);
      }

      return payload;
    };

    if (type === 'Point') {
      const latlng = normalizeCoord(coords);
      if (latlng[0] == null || latlng[1] == null || isNaN(latlng[0]) || isNaN(latlng[1])) {
        console.error('Invalid Point coordinates:', coords);
        return results;
      }
      results.push(makePayload('marker', [latlng]));
    } else if (type === 'LineString') {
      results.push(makePayload('polyline', normalizeLine(coords)));
    } else if (type === 'Polygon') {
      const ring = normalizeLine(coords[0]);
      results.push(makePayload('polygon', ring));
    } else if (type === 'MultiPoint') {
      coords.forEach(c => {
        const latlng = normalizeCoord(c);
        if (latlng[0] == null || latlng[1] == null || isNaN(latlng[0]) || isNaN(latlng[1])) {
          console.error('Invalid MultiPoint coordinates:', c);
          return;
        }
        results.push(makePayload('marker', [latlng]));
      });
    } else if (type === 'MultiLineString') {
      coords.forEach(line => {
        results.push(makePayload('polyline', normalizeLine(line)));
      });
    } else if (type === 'MultiPolygon') {
      coords.forEach(poly => {
        const ring = normalizeLine(poly[0]);
        results.push(makePayload('polygon', ring));
      });
    } else if (type === 'GeometryCollection') {
      geometry.geometries.forEach(geom => {
        results.push(...normalizeGeoJsonFeature({
          properties: feature.properties,
          geometry: geom
        }));
      });
    }

    return results;
  }

  window.handleKmlImport = async function(event) {
    console.log('handleKmlImport function triggered');
    const file = event.target.files?.[0];
    if (!file) {
      console.error('No file selected for import');
      return;
    }

    try {
      Swal.fire({
        title: 'Mengimpor...',
        text: 'Sedang memproses file KML/KMZ',
        allowOutsideClick: false,
        onOpen: () => {
          console.log('SweetAlert opened successfully');
          Swal.showLoading();
        }
      });

      // Cek JSZip
      if (typeof JSZip === 'undefined') {
        console.error('JSZip library is not loaded!');
        throw new Error('Library JSZip tidak ditemukan. Pastikan <script src> JSZip sudah dimuat.');
      }


      // Cek toGeoJSON
      console.log('Ensuring toGeoJSON library is loaded');
      await ensureToGeoJsonLoaded();
      console.log('Setelah ensureToGeoJsonLoaded, cek window.toGeoJSON:', typeof window.toGeoJSON, 'window.togeojson:', typeof window.togeojson);
      let toGeo = null;
      if (typeof window.toGeoJSON === 'object' || typeof window.toGeoJSON === 'function') {
        toGeo = window.toGeoJSON;
        console.log('Menggunakan window.toGeoJSON');
      } else if (typeof window.togeojson === 'object' || typeof window.togeojson === 'function') {
        toGeo = window.togeojson;
        console.log('Menggunakan window.togeojson');
      } else {
        console.error('toGeoJSON library is not available in the global scope');
        Swal.close();
        throw new Error('Pustaka toGeoJSON tidak tersedia. Pastikan <script src> toGeoJSON sudah dimuat dan tidak diblokir browser.');
      }
      console.log('toGeoJSON library loaded successfully');

      // Cek file type
      console.log('Reading KML file...');
      let kmlText = '';
      try {
        kmlText = await readKmlFromFile(file);
      } catch (err) {
        console.error('Error reading file:', err);
        throw new Error('Gagal membaca file KML/KMZ: ' + (err.message || err));
      }
      if (!kmlText) {
        throw new Error('File KML/KMZ kosong atau gagal dibaca.');
      }
      console.log('KML content read successfully:', kmlText ? kmlText.slice(0, 100) : 'No content');


      // Parse XML
      let xmlDoc = null;
      try {
        xmlDoc = new DOMParser().parseFromString(kmlText, 'text/xml');
      } catch (err) {
        console.error('Error parsing KML XML:', err);
        throw new Error('File KML tidak valid (gagal parsing XML).');
      }
      if (!xmlDoc || !xmlDoc.documentElement) {
        throw new Error('File KML tidak valid (XML kosong).');
      }
      console.log('KML content parsed successfully:', xmlDoc);

      // toGeoJSON
      if (!toGeo?.kml) {
        console.error('toGeoJSON library is not loaded or invalid (tidak ada fungsi kml)');
        throw new Error('Library KML parser belum terload (fungsi kml tidak ditemukan)');
      }
      console.log('toGeoJSON library is valid');

      let geojson = null;
      try {
        geojson = toGeo.kml(xmlDoc);
      } catch (err) {
        console.error('Error converting KML to GeoJSON:', err);
        throw new Error('Gagal mengkonversi KML ke GeoJSON: ' + (err.message || err));
      }
      console.log('GeoJSON generated:', geojson);

      if (!geojson?.features?.length) {
        console.error('No geometry data found in KML/KMZ file');
        throw new Error('Tidak ada data geometri dalam file KML/KMZ');
      }

      console.log('Normalizing GeoJSON features');
      const payloads = [];
      geojson.features.forEach(feature => {
        console.log('Normalizing feature:', feature);
        payloads.push(...normalizeGeoJsonFeature(feature));
      });

      if (!payloads.length) {
        console.error('No valid geometry found to import');
        throw new Error('Tidak ada geometry yang bisa diimpor dari file ini');
      }

      console.log('Saving imported layers to server');
      const results = await Promise.allSettled(payloads.map(p => saveImportedLayer(p)));
      console.log('Save results:', results);

      const successCount = results.filter(r => r.status === 'fulfilled').length;
      const failCount = results.length - successCount;

      console.log(`Import completed: ${successCount} succeeded, ${failCount} failed`);
      Swal.fire({
        icon: failCount > 0 ? 'warning' : 'success',
        title: 'Import Selesai',
        text: `Berhasil: ${successCount} layer. Gagal: ${failCount} layer.`,
        confirmButtonText: 'OK'
      });

      loadSavedLayers();
    } catch (error) {
      console.error('Error during KML import:', error);
      Swal.fire({
        icon: 'error',
        title: 'Gagal Import',
        text: error.message || 'Terjadi kesalahan saat membaca file KML/KMZ',
        confirmButtonText: 'OK'
      });
    }
  };

  window.exportLayersToKml = function() {
    if (!window.savedLayersData?.length) {
      Swal.fire({
        icon: 'info',
        title: 'Tidak ada layer',
        text: 'Tidak ada data untuk diexport',
        confirmButtonText: 'OK'
      });
      return;
    }

    const getCoords = (layer) => {
      if (Array.isArray(layer.coordinates)) return layer.coordinates;
      try {
        return JSON.parse(layer.coordinates);
      } catch (e) {
        return [];
      }
    };

    // Gunakan string biasa, hindari template string multiline jika error
    const kmlHeader = '<?xml version="1.0" encoding="UTF-8"?>\n';
      '<kml xmlns="http://www.opengis.net/kml/2.2">\n<Document>\n';
    const kmlFooter = '</Document>\n</kml>';

    const placemarks = window.savedLayersData.map(layer => {
      const name = layer.name || `Layer #${layer.id}`;
      const desc = layer.description || '';
      const coordsData = getCoords(layer);
      if (!coordsData) return '';
      if (layer.type === 'marker') {
        const [lat, lng] = coordsData[0] || [];
        if (lat == null || lng == null) return '';
        return '  <Placemark>\n    <name>' + name + '</name>\n    <description>' + desc + '</description>\n    <Point><coordinates>' + lng + ',' + lat + ',0</coordinates></Point>\n  </Placemark>';
      }
      if (layer.type === 'polyline') {
        const coords = coordsData.map(c => c[1] + ',' + c[0] + ',0').join(' ');
        return '  <Placemark>\n    <name>' + name + '</name>\n    <description>' + desc + '</description>\n    <LineString><coordinates>' + coords + '</coordinates></LineString>\n  </Placemark>';
      }
      if (layer.type === 'polygon' || layer.type === 'rectangle') {
        const ring = [...coordsData];
        const first = ring[0];
        const last = ring[ring.length - 1];
        if (first && last && (first[0] !== last[0] || first[1] !== last[1])) {
          ring.push(first);
        }
        const coords = ring.map(c => c[1] + ',' + c[0] + ',0').join(' ');
        return '  <Placemark>\n    <name>' + name + '</name>\n    <description>' + desc + '</description>\n    <Polygon><outerBoundaryIs><LinearRing><coordinates>' + coords + '</coordinates></LinearRing></outerBoundaryIs></Polygon>\n  </Placemark>';
      }
      return '';
    }).filter(Boolean).join('\n');

    const kmlContent = kmlHeader + placemarks + '\n' + kmlFooter;
    const blob = new Blob([kmlContent], { type: 'application/vnd.google-earth.kml+xml' });
    const url = URL.createObjectURL(blob);

    const link = document.createElement('a');
    link.href = url;
    link.download = `layers_${new Date().toISOString().slice(0,10)}.kml`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
  };

  // Fungsi untuk load saved layers
  function loadSavedLayers() {
    console.log('Loading saved layers...');
    $.ajax({
      url: '/map/layers',
      method: 'GET',
      success: function(layers) {
        console.log('Layers received:', layers);
        console.log('Total layers:', layers.length);
        
        // Don't clear layers - just update what's needed
        // savedLayersGroup.clearLayers();
        window.savedLayersData = layers;
        
        // Get current layer IDs in the group
        const currentLayerIds = Object.keys(window.layerObjects).map(id => parseInt(id));
        const newLayerIds = layers.map(l => l.id);
        
        console.log('Current layer IDs:', currentLayerIds);
        console.log('New layer IDs:', newLayerIds);
        
        // Remove deleted layers
        currentLayerIds.forEach(id => {
          if (!newLayerIds.includes(id)) {
            const layer = window.layerObjects[id];
            if (layer) {
              savedLayersGroup.removeLayer(layer);
              delete window.layerObjects[id];
            }
          }
        });
        
        // Add or update layers
        layers.forEach(layer => {
          // SKIP layer dengan type='group' (ini hanya untuk UI sidebar, bukan untuk map)
          if (layer.type === 'group') {
            console.log('Skipping group type (UI only):', layer.id, layer.name);
            return;
          }
          
          // Check if layer already exists
          if (window.layerObjects[layer.id]) {
            console.log('Layer already exists:', layer.id);
            // Pastikan layer ada di map (cluster atau group)
            const leafletLayer = window.layerObjects[layer.id];
            const isMarker = layer.type === 'marker';
            
            if (isMarker) {
              // Cek apakah marker sudah ada di cluster
              if (!window.importedMarkerCluster.hasLayer(leafletLayer)) {
                console.log('Re-adding marker to cluster:', layer.id);
                window.importedMarkerCluster.addLayer(leafletLayer);
              }
            } else {
              // Cek apakah layer sudah ada di group
              if (!savedLayersGroup.hasLayer(leafletLayer)) {
                console.log('Re-adding layer to group:', layer.id);
                savedLayersGroup.addLayer(leafletLayer);
              }
            }
            return;
          }
          
          console.log('Creating layer:', layer.id, layer.name);
          // Coordinates sudah array dari accessor model, tidak perlu parse
          const coords = layer.coordinates;
          let leafletLayer;

          if (layer.type === 'polyline') {
            leafletLayer = L.polyline(coords, {
              color: layer.color,
              weight: layer.weight,
              opacity: layer.opacity
            });
            
            // Add cable info badge if this is fiber cable
            if (layer.description && layer.description.includes('🌐 Fiber:')) {
              // Extract fiber info from description
              const fiberMatch = layer.description.match(/🌐 Fiber: (\d+)C \| (\w+) \| (\w+)/);
              if (fiberMatch) {
                const [, coreCount, cableType, status] = fiberMatch;
                const distanceKm = (layer.distance / 1000).toFixed(2);
                
                // Calculate center point of polyline
                const bounds = leafletLayer.getBounds();
                const center = bounds.getCenter();
                
                // Status colors
                const statusColors = {
                  Planned: '#3b82f6',
                  Installed: '#22c55e',
                  Active: '#10b981',
                  Damaged: '#ef4444',
                  Reserved: '#f59e0b'
                };
                const badgeColor = statusColors[status] || '#64748b';
                
                // Create floating badge
                const badge = L.marker(center, {
                  icon: L.divIcon({
                    html: `<div style="background: ${badgeColor}; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; white-space: nowrap; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">${coreCount}C · ${distanceKm}km · ${cableType}</div>`,
                    className: 'cable-info-badge',
                    iconSize: [null, null],
                    iconAnchor: [0, 0]
                  }),
                  interactive: false
                });
                badge.addTo(savedLayersGroup);
                
                // Store badge reference to remove when layer is deleted
                if (!window.cableBadges) window.cableBadges = {};
                window.cableBadges[layer.id] = badge;
              }
            }
          } else if (layer.type === 'polygon' || layer.type === 'rectangle') {
            leafletLayer = L.polygon(coords, {
              color: layer.color,
              weight: layer.weight,
              opacity: layer.opacity,
              fillOpacity: layer.opacity * 0.5
            });
          } else if (layer.type === 'marker') {
            // Cek apakah ini text label (icon='text')
            if (layer.icon === 'text') {
              // TEXT/LABEL LAYER
              console.log('Creating text layer:', layer.id, layer.name);
              const textCoords = coords[0]; // [lat, lng]
              if (!Array.isArray(textCoords) || textCoords.length < 2) {
                console.error('Invalid text coordinates:', textCoords);
                return;
              }
              
              const fontSize = layer.weight || 14; // weight untuk fontSize
              const color = layer.color || '#000000';
              const useBg = layer.opacity > 0.5; // opacity > 0.5 = ada background
              const text = layer.name || 'Text';
              
              const bgStyle = useBg ? 'background: white; padding: 4px 8px; border-radius: 4px; box-shadow: 0 2px 6px rgba(0,0,0,0.3);' : '';
              
              leafletLayer = L.marker(textCoords, {
                icon: L.divIcon({
                  html: `<div style="color: ${color}; font-size: ${fontSize}px; font-weight: bold; white-space: nowrap; ${bgStyle}">${text}</div>`,
                  className: 'text-label-marker',
                  iconSize: [null, null],
                  iconAnchor: [0, 0]
                })
              });
            } else {
              // Regular marker dengan icon biasa
              const iconType = layer.icon || 'pin';
              const icon = createMarkerIcon(iconType, layer.color);
              // Validate coords[0] is [lat, lng]
              let markerLatLng = coords[0];
              if (!Array.isArray(markerLatLng) || markerLatLng.length < 2 || isNaN(markerLatLng[0]) || isNaN(markerLatLng[1])) {
                console.error('Invalid marker coordinates:', markerLatLng);
                return;
              }
              leafletLayer = L.marker(markerLatLng, { icon: icon });
            }
          } else if (layer.type === 'text') {
            // TEXT/LABEL LAYER - NEW!
            console.log('Creating text layer:', layer.id, layer.name);
            const textCoords = coords[0]; // [lat, lng]
            if (!Array.isArray(textCoords) || textCoords.length < 2) {
              console.error('Invalid text coordinates:', textCoords);
              return;
            }
            
            const fontSize = layer.weight || 14; // weight digunakan untuk simpan fontSize
            const color = layer.color || '#000000';
            const useBg = layer.opacity > 0.5; // opacity > 0.5 = ada background
            const text = layer.name || 'Text';
            
            const bgStyle = useBg ? 'background: white; padding: 4px 8px; border-radius: 4px; box-shadow: 0 2px 6px rgba(0,0,0,0.3);' : '';
            
            leafletLayer = L.marker(textCoords, {
              icon: L.divIcon({
                html: `<div style="color: ${color}; font-size: ${fontSize}px; font-weight: bold; white-space: nowrap; ${bgStyle}">${text}</div>`,
                className: 'text-label-marker',
                iconSize: [null, null],
                iconAnchor: [0, 0]
              })
            });
          }

          if (leafletLayer) {
            let popupContent = `<strong>${layer.name || 'Layer #' + layer.id}</strong><br>`;
            if (layer.description) popupContent += `${layer.description}<br>`;
            if (layer.type === 'marker') popupContent += `📍 Marker<br>`;
            if (layer.type === 'text') popupContent += `📝 Text/Label<br>`;
            if (layer.distance) popupContent += `📏 ${layer.distance} m<br>`;
            if (layer.area) popupContent += `📐 ${layer.area} m²<br>`;
            popupContent += `<small>📅 ${new Date(layer.created_at).toLocaleDateString('id-ID')}</small><br>`;
            popupContent += `<button class="btn btn-sm btn-danger mt-2" onclick="deleteLayer(${layer.id})">
              <i class="fas fa-trash"></i> Hapus
            </button>`;

            leafletLayer.bindPopup(popupContent);
            if (layer.type === 'marker') {
              // Check visibility state from localStorage
              const shouldBeVisible = getLayerVisibility(layer.id);
              if (shouldBeVisible) {
                console.log('✅ Adding marker to cluster:', layer.id, layer.name);
                window.importedMarkerCluster.addLayer(leafletLayer);
              } else {
                console.log('🙈 Skipping hidden marker:', layer.id, layer.name);
              }
            } else {
              // Check visibility state from localStorage
              const shouldBeVisible = getLayerVisibility(layer.id);
              if (shouldBeVisible) {
                console.log('✅ Adding', layer.type, 'to savedLayersGroup:', layer.id, layer.name);
                savedLayersGroup.addLayer(leafletLayer);
              } else {
                console.log('🙈 Skipping hidden', layer.type, ':', layer.id, layer.name);
              }
            }
            // Store reference
            window.layerObjects[layer.id] = leafletLayer;
            console.log('📌 Layer stored in layerObjects:', layer.id);
          }
        });
        
        console.log('Total layers in layerObjects:', Object.keys(window.layerObjects).length);
        console.log('Polylines/polygons in savedLayersGroup:', savedLayersGroup.getLayers().length);
        console.log('Markers in importedMarkerCluster:', window.importedMarkerCluster.getLayers().length);
        
        // Update layer list UI
        updateLayerList();
      },
      error: function(xhr) {
        console.error('Error loading layers:', xhr);
        console.error('Status:', xhr.status);
        console.error('Response:', xhr.responseText);
        updateLayerList(); // Update UI even on error
      }
    });
  }

  // Fungsi untuk menghapus layer
  window.deleteLayer = function(layerId) {
    Swal.fire({
      title: 'Hapus Layer?',
      text: 'Layer yang dihapus tidak dapat dikembalikan!',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Ya, Hapus!',
      cancelButtonText: 'Batal'
    }).then((result) => {
      if (!result.isConfirmed) return;

    $.ajax({
      url: '/map/layers/' + layerId,
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      success: function() {
        // Remove from map immediately
        const layer = window.layerObjects[layerId];
        if (layer) {
          savedLayersGroup.removeLayer(layer);
          delete window.layerObjects[layerId];
        }
        
        // Remove from data array
        window.savedLayersData = window.savedLayersData.filter(l => l.id !== layerId);
        
        // Update UI
        updateLayerList();
        
        Swal.fire({
          icon: 'success',
          title: 'Berhasil!',
          text: 'Layer berhasil dihapus',
          timer: 2000,
          showConfirmButton: false
        });
      },
      error: function() {
        Swal.fire({
          icon: 'error',
          title: 'Gagal Menghapus',
          text: 'Terjadi kesalahan saat menghapus layer',
          confirmButtonText: 'OK'
        });
      }
    });
    });
  };

  // Toggle layer sidebar
  window.toggleLayerSidebar = function() {
    const sidebar = document.getElementById('layerSidebar');
    const icon = document.getElementById('sidebarToggleIcon');
    
    sidebar.classList.toggle('collapsed');
    
    if (sidebar.classList.contains('collapsed')) {
      icon.className = 'fas fa-chevron-right';
    } else {
      icon.className = 'fas fa-chevron-left';
    }
  };

  // Zoom to specific layer
  window.zoomToLayer = function(layerId) {
    const leafletLayer = window.layerObjects[layerId];
    if (!leafletLayer) {
      console.warn('❌ Layer not found:', layerId);
      return;
    }
    
    // Check layer type
    const layer = (window.savedLayersData || []).find(l => l.id === layerId);
    if (layer && layer.type === 'marker') {
      // Marker uses getLatLng
      const latlng = leafletLayer.getLatLng();
      map.setView(latlng, 18);
      leafletLayer.openPopup();
    } else {
      // Polyline/Polygon uses getBounds
      if (leafletLayer.getBounds) {
        const bounds = leafletLayer.getBounds();
        map.fitBounds(bounds, { padding: [50, 50] });
      }
      if (leafletLayer.openPopup) leafletLayer.openPopup();
    }
  };

  // OLD Edit layer function using L.popup() - DEPRECATED
  // Now using SweetAlert2-based edit with Geoman visual editing (see line ~1396)
  // Removed to prevent conflicts with new editLayer() function
  
  // Toggle layer visibility function moved to line ~676 (removed duplicate to prevent conflicts)

  // Load saved layers on page load
  loadSavedLayers();


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
    <div class="form-check mb-2">
    <input class="form-check-input" type="checkbox" id="show-tickets" checked />
    <label class="form-check-label" for="show-tickets">Tampilkan Tiket</label>
    </div>
    <div class="form-check">
    <input class="form-check-input" type="checkbox" id="show-saved-layers" checked />
    <label class="form-check-label" for="show-saved-layers">Tampilkan Layer Tersimpan</label>
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
      // Cek apakah ODP memiliki parent dengan koordinat yang valid
      if (
        odp.parent_lat &&
        odp.parent_lng &&
        odp.lat &&
        odp.lng
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
          opacity: 0.6,
          dashArray: '5, 10'
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
      position: 'topright' // dipindah ke kanan atas
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

  // Cek apakah checkbox Saved Layers dicentang
  if (document.getElementById('show-saved-layers').checked) {
    map.addLayer(savedLayersGroup); // Menambahkan saved layers
  } else {
    map.removeLayer(savedLayersGroup); // Menghapus saved layers
  }
}

// === Checkbox Event Listener ===
document.getElementById('show-odp').addEventListener('change', updateMapLayers);
document.getElementById('show-tickets').addEventListener('change', updateMapLayers);
document.getElementById('show-saved-layers').addEventListener('change', updateMapLayers);

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
  Swal.fire({
    icon: 'warning',
    title: 'Lokasi Tidak Tersedia',
    text: 'Tidak bisa mendapatkan lokasi Anda. Pastikan izin lokasi diaktifkan.',
    confirmButtonText: 'OK'
  });
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
