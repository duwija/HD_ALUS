# 🌐 Fiber Optic Map Features - Implementation Guide

## 📋 Ringkasan Status Implementasi

### ✅ **Sudah Tersedia** (Built-in):
1. **Polyline Drawing** - Untuk cable routing
2. **Distance Calculation** - Auto-calculate panjang kabel
3. **Color Selection** - Pilih warna untuk cable
4. **Layer Grouping** - Organize cables by group
5. **Marker Icons** - Pin, Tower, Router, Server, Building, Home
6. **Edit Mode** - Drag to edit cable route
7. **Export/Import** - KML/KMZ support

---

## 🚀 **Fitur Prioritas untuk Fiber Optic**

### **Phase 1: Quick Wins (1-2 Hari)**

#### 1. **Enhanced Cable Metadata**
**Tambahkan field khusus fiber optic:**

```html
<!-- Tambah di popup polyline creation -->
<div class="form-check mb-2">
  <input type="checkbox" id="is-fiber-cable" class="form-check-input">
  <label for="is-fiber-cable">🌐 Fiber Optic Cable</label>
</div>

<div id="fiber-options" style="display:none;">
  <!-- Cable Type -->
  <label>Cable Type:</label>
  <select id="cable-type" class="form-select form-select-sm mb-2">
    <option value="aerial">Aerial (Udara)</option>
    <option value="underground">Underground (Tanah)</option>
    <option value="duct">Duct (Pipa)</option>
    <option value="underwater">Underwater (Air)</option>
  </select>
  
  <!-- Core Count -->
  <label>Core Count:</label>
  <select id="core-count" class="form-select form-select-sm mb-2">
    <option value="12">12 Core</option>
    <option value="24">24 Core</option>
    <option value="48">48 Core</option>
    <option value="96">96 Core</option>
    <option value="144">144 Core</option>
    <option value="288">288 Core</option>
  </select>
  
  <!-- Status -->
  <label>Status:</label>
  <select id="cable-status" class="form-select form-select-sm">
    <option value="planned" style="color:#3b82f6">🔵 Planned</option>
    <option value="installed" style="color:#22c55e">🟢 Installed</option>
    <option value="active" style="color:#10b981">✅ Active</option>
    <option value="damaged" style="color:#ef4444">🔴 Damaged</option>
    <option value="reserved" style="color:#f59e0b">🟡 Reserved</option>
  </select>
</div>

<script>
// Toggle fiber options
document.getElementById('is-fiber-cable').addEventListener('change', function(e) {
  document.getElementById('fiber-options').style.display = e.target.checked ? 'block' : 'none';
});

// Auto-color by status
document.getElementById('cable-status').addEventListener('change', function(e) {
  const colors = {
    planned: '#3b82f6',
    installed: '#22c55e',
    active: '#10b981',
    damaged: '#ef4444',
    reserved: '#f59e0b'
  };
  selectColor(colors[e.target.value] || '#3388ff');
});
</script>
```

**Database Update:**
Simpan info fiber di field `description`:
```
Format: "🌐 48C | Underground | Active | Original description"
```

---

#### 2. **Splice Point & Infrastructure Icons**

**Tambah 3 icon baru ke marker:**

```javascript
// Di bagian icon selector marker
const fiberIcons = {
  splice: {
    icon: 'fa-link',
    label: 'Splice/Joint',
    color: '#1e40af'
  },
  splitter: {
    icon: 'fa-code-branch',
    label: 'Optical Splitter',
    color: '#7c3aed'
  },
  manhole: {
    icon: 'fa-circle',
    label: 'Manhole',
    color: '#64748b'
  }
};

// Update createMarkerIcon function
function createMarkerIcon(iconType, color) {
  const iconMap = {
    pin: 'fa-map-pin',
    home: 'fa-home',
    building: 'fa-building',
    tower: 'fa-broadcast-tower',
    router: 'fa-network-wired',
    server: 'fa-server',
    splice: 'fa-link',      // NEW
    splitter: 'fa-code-branch',  // NEW
    manhole: 'fa-circle'     // NEW
  };
  
  const iconClass = iconMap[iconType] || 'fa-map-pin';
  const iconColor = color || '#a3301c';
  
  // Special styling for fiber infrastructure
  const isFiber = ['splice', 'splitter', 'manhole'].includes(iconType);
  const bgStyle = isFiber ? 'background: #1e40af; padding: 6px; border-radius: 50%;' : '';
  
  return L.divIcon({
    html: `<i class="fas ${iconClass}" style="color: ${isFiber ? 'white' : iconColor}; font-size: 20px; ${bgStyle}"></i>`,
    className: 'fiber-marker',
    iconSize: [32, 32],
    iconAnchor: [16, 32]
  });
}
```

---

#### 3. **Cable Info Badge**

**Tampilkan floating badge di tengah cable:**

```javascript
// Setelah render polyline dari database
if (layer.type === 'polyline' && layer.description.includes('🌐')) {
  // Extract: "🌐 48C | Underground | Active"
  const match = layer.description.match(/🌐 (\d+)C \| (\w+) \| (\w+)/);
  
  if (match) {
    const [, coreCount, cableType, status] = match;
    const distance = (layer.distance / 1000).toFixed(2); // km
    
    // Get center of polyline
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
    
    // Create badge marker
    const badge = L.marker(center, {
      icon: L.divIcon({
        html: `<div style="
          background: ${statusColors[status]};
          color: white;
          padding: 4px 10px;
          border-radius: 6px;
          font-size: 11px;
          font-weight: bold;
          white-space: nowrap;
          box-shadow: 0 2px 8px rgba(0,0,0,0.4);
        ">${coreCount}C · ${distance}km · ${cableType}</div>`,
        className: 'cable-badge',
        iconSize: [null, null]
      }),
      interactive: false,
      zIndexOffset: 1000
    }).addTo(map);
    
    // Store untuk cleanup
    window.cableBadges = window.cableBadges || {};
    window.cableBadges[layer.id] = badge;
  }
}
```

**CSS untuk badge:**
```css
.cable-badge {
  pointer-events: none;
  user-select: none;
}
```

---

### **Phase 2: Advanced Features (3-5 Hari)**

#### 4. **Multi-Segment Cable Drawing**

**Kemampuan draw cable dengan multiple intermediate points:**

```javascript
// Global variables
let multiSegmentMode = false;
let segments = [];
let totalDistance = 0;

// Button to enable multi-segment
const MultiSegmentButton = L.Control.extend({
  options: { position: 'topleft' },
  onAdd: function(map) {
    const btn = L.DomUtil.create('button', 'btn btn-sm btn-primary');
    btn.innerHTML = '<i class="fas fa-route"></i> Multi-Segment Cable';
    btn.onclick = () => toggleMultiSegmentMode();
    return btn;
  }
});

function toggleMultiSegmentMode() {
  multiSegmentMode = !multiSegmentMode;
  if (multiSegmentMode) {
    map.getContainer().style.cursor = 'crosshair';
    Swal.fire({
      icon: 'info',
      title: 'Multi-Segment Mode',
      text: 'Click points on map. Double-click to finish.',
      timer: 3000
    });
  } else {
    finishMultiSegment();
  }
}

map.on('click', function(e) {
  if (!multiSegmentMode) return;
  
  segments.push(e.latlng);
  
  if (segments.length > 1) {
    // Draw temporary line
    const tempLine = L.polyline(segments, {
      color: '#3b82f6',
      weight: 3,
      dashArray: '5, 5'
    }).addTo(map);
    
    // Calculate distance
    const lastDist = segments[segments.length - 2].distanceTo(segments[segments.length - 1]);
    totalDistance += lastDist;
    
    // Show distance popup
    L.popup()
      .setLatLng(e.latlng)
      .setContent(`Segment ${segments.length - 1}: ${lastDist.toFixed(0)}m<br>Total: ${totalDistance.toFixed(0)}m`)
      .openOn(map);
  } else {
    // First point marker
    L.marker(e.latlng, {
      icon: L.divIcon({
        html: '<div style="background:#3b82f6; color:white; width:24px; height:24px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:bold;">1</div>'
      })
    }).addTo(map);
  }
});

map.on('dblclick', function(e) {
  if (multiSegmentMode) {
    finishMultiSegment();
  }
});

function finishMultiSegment() {
  if (segments.length < 2) return;
  
  // Create final polyline
  const finalLine = L.polyline(segments, {
    color: '#22c55e',
    weight: 4
  }).addTo(map);
  
  // Show save dialog with total distance
  Swal.fire({
    title: `Cable Route: ${totalDistance.toFixed(0)}m`,
    html: `
      <input id="cable-name" class="form-control" placeholder="Cable ID">
      <p class="mt-2">${segments.length} points, ${totalDistance.toFixed(0)} meters</p>
    `,
    showCancelButton: true
  }).then(result => {
    if (result.isConfirmed) {
      // Save to database
      saveCableRoute(segments, totalDistance);
    }
  });
  
  // Reset
  segments = [];
  totalDistance = 0;
  multiSegmentMode = false;
}
```

---

#### 5. **Core Allocation Tracker**

**Track which cores are used/available:**

```html
<!-- Core Allocation Dialog -->
<div id="core-allocation-modal">
  <h5>Fiber Core Allocation</h5>
  <div class="core-grid">
    <!-- 48 cores example -->
    <div class="core-item available" data-core="1">
      <span>1</span>
      <span class="status">🟢</span>
    </div>
    <!-- ... cores 2-48 ... -->
  </div>
  
  <div class="legend">
    <span>🟢 Available</span>
    <span>🟡 Reserved</span>
    <span>🔴 Used</span>
    <span>⚫ Damaged</span>
  </div>
</div>

<style>
.core-grid {
  display: grid;
  grid-template-columns: repeat(8, 1fr);
  gap: 8px;
}

.core-item {
  padding: 8px;
  border: 2px solid #ddd;
  border-radius: 6px;
  text-align: center;
  cursor: pointer;
}

.core-item.available { border-color: #22c55e; }
.core-item.reserved { border-color: #f59e0b; }
.core-item.used { border-color: #ef4444; }
.core-item.damaged { border-color: #64748b; }
</style>
```

**Database schema tambahan:**
```sql
CREATE TABLE fiber_core_allocation (
  id INT PRIMARY KEY AUTO_INCREMENT,
  layer_id INT,
  core_number INT,
  status ENUM('available', 'reserved', 'used', 'damaged'),
  allocated_to VARCHAR(255),
  notes TEXT,
  FOREIGN KEY (layer_id) REFERENCES map_layers(id)
);
```

---

#### 6. **Signal Path Tracer**

**Highlight jalur dari customer ke OLT:**

```javascript
function traceSignalPath(customerMarkerId) {
  // 1. Find customer marker
  const customer = window.savedLayersData.find(l => l.id === customerMarkerId);
  
  // 2. Find connected ODP (nearest polyline endpoint)
  const nearestODP = findNearestPolylineEndpoint(customer.coordinates[0]);
  
  // 3. Trace back to OLT
  const path = [];
  let currentPoint = nearestODP;
  
  while (currentPoint.type !== 'OLT') {
    path.push(currentPoint);
    currentPoint = findNextHop(currentPoint);
  }
  
  // 4. Calculate total loss
  let totalLoss = 0;
  path.forEach(segment => {
    // Fiber attenuation: 0.35 dB/km
    totalLoss += (segment.distance / 1000) * 0.35;
    
    // Splice loss: 0.1 dB per splice
    if (segment.hasSplice) totalLoss += 0.1;
    
    // Connector loss: 0.5 dB
    if (segment.hasConnector) totalLoss += 0.5;
  });
  
  // 5. Highlight path
  path.forEach(segment => {
    const layer = window.layerObjects[segment.id];
    if (layer) {
      layer.setStyle({
        color: '#fbbf24',
        weight: 6,
        opacity: 1
      });
      
      // Animate
      let opacity = 1;
      setInterval(() => {
        opacity = opacity === 1 ? 0.3 : 1;
        layer.setStyle({ opacity });
      }, 500);
    }
  });
  
  // 6. Show result
  Swal.fire({
    icon: totalLoss < 28 ? 'success' : 'warning',
    title: 'Signal Path Analysis',
    html: `
      <p><strong>Total Distance:</strong> ${path.reduce((sum, s) => sum + s.distance, 0).toFixed(0)}m</p>
      <p><strong>Total Loss:</strong> ${totalLoss.toFixed(2)} dB</p>
      <p><strong>Status:</strong> ${totalLoss < 28 ? '✅ Good' : '⚠️ High Loss'}</p>
      <p><strong>Hops:</strong> ${path.length}</p>
    `
  });
}
```

---

### **Phase 3: Enterprise Features (1-2 Minggu)**

#### 7. **Network Topology Tree View**

```html
<div id="topology-sidebar">
  <h5>Network Topology</h5>
  <div class="tree">
    <ul>
      <li>
        <span class="tree-node olt">
          <i class="fas fa-server"></i> OLT-CENTRAL-01
        </span>
        <ul>
          <li>
            <span class="tree-node splitter">
              <i class="fas fa-code-branch"></i> Splitter 1:8
            </span>
            <ul>
              <li><span class="tree-node odp">📍 ODP-001 (8 ports)</span></li>
              <li><span class="tree-node odp">📍 ODP-002 (8 ports)</span></li>
            </ul>
          </li>
        </ul>
      </li>
    </ul>
  </div>
</div>
```

---

#### 8. **Coverage Area Heatmap**

```javascript
// Hitung coverage berdasarkan radius dari ODP
function generateCoverageHeatmap() {
  const odpMarkers = window.savedLayersData.filter(l => 
    l.type === 'marker' && l.name.includes('ODP')
  );
  
  const heatmapData = [];
  
  odpMarkers.forEach(odp => {
    const [lat, lng] = odp.coordinates[0];
    
    // Radius 500m untuk coverage area
    const circle = L.circle([lat, lng], {
      radius: 500,
      fillColor: '#22c55e',
      fillOpacity: 0.2,
      stroke: false
    }).addTo(map);
    
    // Count customers dalam radius
    const customersInRange = window.savedLayersData.filter(c => {
      if (c.type !== 'marker') return false;
      const dist = L.latLng(odp.coordinates[0]).distanceTo(c.coordinates[0]);
      return dist <= 500;
    }).length;
    
    // Add tooltip
    circle.bindTooltip(`${odp.name}<br>${customersInRange} customers`);
  });
}
```

---

## 📊 **Database Schema Updates**

### Recommended Additional Tables:

```sql
-- Cable specifications
CREATE TABLE fiber_cables (
  id INT PRIMARY KEY AUTO_INCREMENT,
  layer_id INT,
  cable_type ENUM('aerial', 'underground', 'duct', 'underwater'),
  core_count INT,
  status ENUM('planned', 'installed', 'active', 'damaged', 'reserved'),
  manufacturer VARCHAR(100),
  installation_date DATE,
  warranty_expiry DATE,
  FOREIGN KEY (layer_id) REFERENCES map_layers(id)
);

-- Splice points
CREATE TABLE splice_points (
  id INT PRIMARY KEY AUTO_INCREMENT,
  marker_id INT,
  splice_type VARCHAR(50),
  splice_loss DECIMAL(4,2),
  installation_date DATE,
  photo_url VARCHAR(255),
  FOREIGN KEY (marker_id) REFERENCES map_layers(id)
);

-- Port allocation
CREATE TABLE port_allocation (
  id INT PRIMARY KEY AUTO_INCREMENT,
  odp_id INT,
  port_number INT,
  status ENUM('available', 'used', 'reserved', 'damaged'),
  customer_id INT,
  activation_date DATE,
  FOREIGN KEY (odp_id) REFERENCES map_layers(id)
);
```

---

## 🎯 **Prioritas Implementasi**

### Minggu 1:
- ✅ Cable metadata (type, core count, status)
- ✅ Splice point icons
- ✅ Cable info badges

### Minggu 2:
- Multi-segment drawing
- Core allocation tracker
- Signal path tracer

### Minggu 3-4:
- Network topology view
- Coverage heatmap
- Loss calculator
- Photo documentation

---

## 📝 **Catatan Implementasi**

1. **Backward Compatibility**: Gunakan field `description` untuk store fiber metadata
2. **Performance**: Badge rendering hanya untuk visible layers
3. **Mobile**: Touch-friendly controls
4. **Export**: Include fiber data in KML export

---

**Butuh bantuan implementasi?** Pilih fitur yang mau diimplementasikan dulu, saya akan buatkan kode lengkapnya!
