# 🗺️ Map Layer System - Panduan Lengkap

## 📋 Overview

Sistem persistent layer untuk menyimpan gambar/drawing di peta, mirip dengan Google Maps/Earth. Layer yang digambar akan tersimpan ke database dan dapat dimuat kembali.

---

## 🎯 Fitur Utama

### ✅ Yang Sudah Diimplementasikan:

1. **Drawing Tools** (Leaflet Geoman):
   - ✏️ Polyline (garis/jalur)
   - 🔷 Polygon (area)
   - ⬜ Rectangle (persegi panjang)

2. **Auto-Calculation**:
   - 📏 Jarak otomatis untuk polyline (meter)
   - 📐 Luas otomatis untuk polygon/rectangle (meter persegi)

3. **Persistent Storage**:
   - 💾 Simpan layer ke database
   - 🔄 Load otomatis saat buka peta
   - 🗑️ Hapus layer yang tidak diperlukan

4. **Layer Properties**:
   - 🏷️ Nama layer (opsional)
   - 📝 Deskripsi/catatan
   - 🎨 Warna, ketebalan, transparansi
   - 👤 Track siapa yang membuat
   - 📅 Timestamp pembuatan

5. **Layer Control**:
   - ☑️ Toggle visibility layer tersimpan
   - ☑️ Toggle ODP markers
   - ☑️ Toggle ticket markers

---

## 🗄️ Database Structure

### Tabel: `map_layers`

| Field | Type | Description |
|-------|------|-------------|
| `id` | bigint | Primary key |
| `name` | varchar | Nama layer (opsional) |
| `type` | varchar | Tipe: polyline, polygon, rectangle |
| `coordinates` | text | JSON koordinat lat/lng |
| `color` | varchar | Warna layer (default: #3388ff) |
| `weight` | int | Ketebalan garis (default: 3) |
| `opacity` | decimal | Transparansi 0-1 (default: 0.6) |
| `description` | text | Deskripsi/catatan |
| `distance` | decimal | Jarak (meter) untuk polyline |
| `area` | decimal | Luas (m²) untuk polygon |
| `created_by` | bigint | User ID pembuat |
| `is_visible` | boolean | Visibility status |
| `created_at` | timestamp | Waktu dibuat |
| `updated_at` | timestamp | Waktu update |

---

## 🔧 API Endpoints

### 1️⃣ Get All Saved Layers
```
GET /map/layers
```
**Response:**
```json
[
  {
    "id": 1,
    "name": "Jalur Fiber Utama",
    "type": "polyline",
    "coordinates": "[[lat1,lng1],[lat2,lng2],...]",
    "distance": 1250.5,
    "color": "#ff0000",
    "created_at": "2026-01-28 10:30:00"
  }
]
```

### 2️⃣ Save New Layer
```
POST /map/layers
```
**Request Body:**
```json
{
  "name": "Jalur Fiber Baru",
  "description": "Dari ODP A ke ODP B",
  "type": "polyline",
  "coordinates": "[[lat,lng],...]",
  "distance": 500.25,
  "color": "#3388ff",
  "weight": 3,
  "opacity": 0.6
}
```

### 3️⃣ Update Layer
```
PATCH /map/layers/{id}
```
**Request Body:**
```json
{
  "name": "Nama Baru",
  "description": "Deskripsi update",
  "color": "#ff0000",
  "is_visible": false
}
```

### 4️⃣ Delete Layer
```
DELETE /map/layers/{id}
```

---

## 📖 Cara Penggunaan

### 🖊️ Menggambar Layer Baru

1. **Buka Map**: `kencana.alus.co.id/distpoint/map`

2. **Pilih Tool** di toolbar kiri atas:
   - 📏 **Polyline**: Untuk menggambar garis/jalur
   - 🔷 **Polygon**: Untuk menggambar area
   - ⬜ **Rectangle**: Untuk menggambar persegi

3. **Gambar di Peta**:
   - Klik titik-titik untuk polyline/polygon
   - Double-click untuk selesai (polyline/polygon)
   - Klik-drag untuk rectangle

4. **Popup Otomatis Muncul**:
   - Jarak total (untuk polyline)
   - Luas area (untuk polygon/rectangle)
   - Form input nama & deskripsi

5. **Simpan Layer**:
   - Isi nama (opsional)
   - Isi deskripsi (opsional)
   - Klik **"Simpan Layer"**
   - Layer akan tersimpan ke database

6. **Batal**:
   - Klik **"Batal"** untuk menghapus drawing tanpa menyimpan

---

### 👀 Melihat Layer Tersimpan

**Layer tersimpan otomatis dimuat** saat membuka peta.

**Toggle Visibility**:
- Buka **Layer Control** (kanan atas peta)
- Centang/uncheck **"Tampilkan Layer Tersimpan"**

**Klik Layer** untuk melihat:
- Nama layer
- Deskripsi
- Jarak/luas
- Tanggal dibuat
- Tombol hapus

---

### 🗑️ Menghapus Layer

1. **Klik layer** di peta
2. **Popup muncul** dengan info layer
3. **Klik tombol "Hapus"**
4. **Konfirmasi** penghapusan
5. Layer terhapus dari database

---

## 🎨 Customization

### Mengubah Warna Default

Edit file: `resources/views/distpoint/map.blade.php`

```javascript
color: layer.color,           // Dari database
weight: layer.weight,         // Ketebalan garis
opacity: layer.opacity,       // Transparansi
fillOpacity: layer.opacity * 0.5  // Transparansi isi
```

### Menambah Field Baru

1. **Migration**: Tambah kolom di `create_map_layers_table.php`
2. **Model**: Update `$fillable` di `MapLayer.php`
3. **Controller**: Update validasi & create/update logic
4. **View**: Tambah input di popup form

---

## 🔐 Security & Permissions

### Middleware Applied:
```php
$this->middleware('auth');
$this->middleware('checkPrivilege:admin,noc,accounting,payment,user,marketing');
```

### Who Can:
- ✅ **Semua user** (admin, noc, user, dll): Gambar & simpan layer
- ✅ **Created by tracking**: Sistem track siapa yang membuat
- ⚠️ **Delete**: Belum ada role restriction (semua bisa hapus)

### Recommended Enhancement:
```php
// Hanya creator atau admin yang bisa hapus
public function deleteMapLayer($id)
{
    $layer = \App\MapLayer::findOrFail($id);
    
    if ($layer->created_by !== auth()->id() && auth()->user()->privilege !== 'admin') {
        return response()->json(['error' => 'Unauthorized'], 403);
    }
    
    $layer->delete();
    return response()->json(['success' => true]);
}
```

---

## 🧪 Testing

### Manual Test Checklist:

1. ✅ **Drawing Test**:
   - [ ] Draw polyline → distance calculated?
   - [ ] Draw polygon → area calculated?
   - [ ] Draw rectangle → area calculated?

2. ✅ **Save Test**:
   - [ ] Save dengan nama & deskripsi
   - [ ] Save tanpa nama (harus opsional)
   - [ ] Refresh page → layer masih ada?

3. ✅ **Toggle Test**:
   - [ ] Uncheck "Tampilkan Layer Tersimpan" → hilang?
   - [ ] Check lagi → muncul kembali?

4. ✅ **Delete Test**:
   - [ ] Klik layer → popup muncul?
   - [ ] Klik hapus → konfirmasi?
   - [ ] Layer terhapus dari peta & database?

5. ✅ **Multi-User Test**:
   - [ ] User A buat layer
   - [ ] User B buka peta → layer User A muncul?
   - [ ] User B bisa hapus layer User A? (seharusnya bisa, tapi bisa di-restrict)

---

## 🐛 Troubleshooting

### Layer tidak tersimpan
**Cek:**
- Network tab → error 500?
- Check CSRF token: `{{ csrf_token() }}`
- Database connection OK?
- Migration sudah run?

### Layer tidak muncul setelah refresh
**Cek:**
- `/map/layers` API response → data ada?
- Console error JavaScript?
- Checkbox "Tampilkan Layer Tersimpan" checked?

### Jarak/luas tidak akurat
**Cek:**
- Leaflet GeometryUtil library loaded?
- `L.GeometryUtil.geodesicArea()` available?
- Koordinat format: `[[lat, lng], ...]`

---

## 📦 Dependencies

### JavaScript Libraries:
```html
<!-- Core Leaflet -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- Geometry Utilities (untuk hitung luas) -->
<script src="https://unpkg.com/leaflet-geometryutil@0.9.3/src/leaflet.geometryutil.js"></script>

<!-- Drawing Tools -->
<script src="https://unpkg.com/@geoman-io/leaflet-geoman-free@2.13.0/dist/leaflet-geoman.min.js"></script>

<!-- jQuery (untuk AJAX) -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.0/jquery.min.js"></script>
```

### Laravel Packages:
- Laravel 8.x
- MySQL/MariaDB

---

## 🚀 Future Enhancements

### Recommended Features:

1. **Export/Import**:
   - Export layer sebagai GeoJSON
   - Import layer dari file

2. **Layer Categories**:
   - Group layers by type (fiber, coverage, planning)
   - Color coding by category

3. **Collaboration**:
   - Share layer dengan user tertentu
   - Comment pada layer

4. **Advanced Tools**:
   - Circle drawing dengan radius
   - Measurement tools (distance between points)
   - Snap to ODP markers

5. **Analytics**:
   - Total jarak fiber yang digambar
   - Coverage area calculation
   - Layer statistics

---

## 📝 Code Structure

### Files Modified/Created:

```
app/
├── MapLayer.php                          # Model baru
├── Http/Controllers/
    └── DistpointController.php          # +4 methods baru

database/
└── migrations/
    └── 2026_01_28_104134_create_map_layers_table.php

resources/views/distpoint/
└── map.blade.php                         # Enhanced with save/load

routes/
└── web.php                               # +4 routes baru
```

### Key Functions:

#### JavaScript:
- `saveDrawnLayer()` - Simpan layer ke database
- `loadSavedLayers()` - Load semua layer tersimpan
- `deleteLayer()` - Hapus layer
- `cancelLayer()` - Batal drawing
- `updateMapLayers()` - Toggle visibility

#### PHP:
- `getMapLayers()` - GET all layers
- `saveMapLayer()` - POST new layer
- `updateMapLayer()` - PATCH existing layer
- `deleteMapLayer()` - DELETE layer

---

## ✅ Implementation Summary

**Database**: ✅ Table created with migration
**Model**: ✅ MapLayer model with relations
**Controller**: ✅ 4 CRUD methods
**Routes**: ✅ 4 API endpoints
**View**: ✅ Save/Load UI with popups
**Validation**: ✅ Request validation
**Security**: ✅ Auth middleware + CSRF
**UX**: ✅ Auto-calculate distance/area
**Persistence**: ✅ Database storage
**Layer Control**: ✅ Toggle visibility

---

## 📞 Support

**Issues?** Check:
1. Browser console for JavaScript errors
2. Laravel logs: `storage/logs/laravel.log`
3. Network tab for API responses
4. Database connection

**Documentation**: Lihat kode comments di:
- `resources/views/distpoint/map.blade.php`
- `app/Http/Controllers/DistpointController.php`

---

**Created**: 2026-01-28  
**Version**: 1.0  
**Status**: ✅ Production Ready
