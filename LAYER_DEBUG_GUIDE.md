# 🔍 Debugging Guide - Layer Tidak Muncul Setelah Refresh

## ✅ Status Check

### 1. Database Check
```bash
cd /var/www/kencana.alus.co.id
mysql -u root -p'123456789' -e "USE kencana; SELECT COUNT(*) as total FROM map_layers;"
mysql -u root -p'123456789' -e "USE kencana; SELECT id, name, type, distance FROM map_layers;"
```

**Result:** ✅ **5 layers tersimpan di database**
- Layer 1: test (3470.67 m)
- Layer 2: test (182.64 m)  
- Layer 3: kabel 12 core (2810.03 m)
- Layer 4: dgfgd (1761.49 m)
- Layer 5: www (3422.12 m)

### 2. Route Check
```bash
php artisan route:list | grep "map/layers"
```

**Result:** ✅ **Routes terdaftar**
- GET /map/layers → getMapLayers
- POST /map/layers → saveMapLayer
- PATCH /map/layers/{id} → updateMapLayer
- DELETE /map/layers/{id} → deleteMapLayer

---

## 🐛 Debugging Steps

### Step 1: Open Browser Console
1. Buka peta: `http://kencana.alus.co.id/distpoint/map`
2. Tekan **F12** untuk buka Developer Tools
3. Pilih tab **Console**
4. Refresh page (F5)

### Step 2: Cek Console Logs
Anda akan melihat logs seperti ini:
```
Loading saved layers...
Layers received: [...]
Total layers: 5
Current layer IDs: []
New layer IDs: [1, 2, 3, 4, 5]
Creating layer: 1 test
Layer added to map: 1
Creating layer: 2 test
Layer added to map: 2
...
Total layers in layerObjects: 5
Layers on map: 5
```

### Step 3: Cek Errors
Jika ada error, akan muncul di console:
```
Error loading layers: {...}
Status: 401/403/500
Response: ...
```

---

## 🔧 Possible Issues & Solutions

### Issue 1: Authentication Error (401/403)
**Symptom:** Console shows "Error loading layers: 401/403"

**Solution:** 
Pastikan user sudah login. Route membutuhkan auth middleware.

### Issue 2: AJAX URL Wrong
**Symptom:** Console shows "404 Not Found"

**Check:**
```javascript
console.log('AJAX URL:', '/map/layers');
console.log('Full URL:', window.location.origin + '/map/layers');
```

### Issue 3: JSON Parse Error
**Symptom:** Console shows error saat parse coordinates

**Check database:**
```sql
SELECT id, coordinates FROM map_layers LIMIT 1;
```

Pastikan format valid JSON: `[[-8.66,115.14],[-8.68,115.17]]`

### Issue 4: Layer Group Not Added to Map
**Symptom:** Logs show layers added but not visible

**Check:**
```javascript
console.log('savedLayersGroup on map:', map.hasLayer(savedLayersGroup));
```

**Solution:** Pastikan `savedLayersGroup` added to map:
```javascript
const savedLayersGroup = L.layerGroup().addTo(map);
```

---

## 🧪 Testing Tools

### Test 1: Direct API Test
Buka: `http://kencana.alus.co.id/test-layers.html`

Klik "Test GET /map/layers" → Harus return JSON array

### Test 2: MySQL Direct Query
```bash
cd /var/www/kencana.alus.co.id
mysql -u root -p'123456789' kencana -e "SELECT * FROM map_layers;"
```

### Test 3: Laravel Route Test
```bash
php artisan tinker
```

```php
$layers = \App\MapLayer::all();
echo $layers->count();
echo json_encode($layers);
```

---

## 📋 Checklist Debug Sequence

Jalankan step by step:

- [ ] **Database Check:** Data ada di table?
  ```bash
  mysql -u root -p'123456789' -e "USE kencana; SELECT COUNT(*) FROM map_layers;"
  ```

- [ ] **Route Check:** Routes registered?
  ```bash
  php artisan route:list | grep map/layers
  ```

- [ ] **Login Check:** User sudah login?
  - Buka `/distpoint/map`
  - Cek apakah ada session user

- [ ] **Console Check:** Buka browser console (F12)
  - Refresh page
  - Lihat logs "Loading saved layers..."
  - Cek ada error?

- [ ] **Network Check:** (F12 → Network tab)
  - Refresh page
  - Cari request ke `/map/layers`
  - Klik → lihat Response
  - Status code berapa? (should be 200)
  - Response berisi array layers?

- [ ] **Map Check:** Layer group added?
  ```javascript
  // Paste di console:
  console.log('savedLayersGroup:', savedLayersGroup);
  console.log('Layers in group:', savedLayersGroup.getLayers().length);
  console.log('On map:', map.hasLayer(savedLayersGroup));
  ```

---

## 🎯 Expected Console Output (Success)

```
Loading saved layers...
Layers received: Array(5)
  0: {id: 1, name: "test", type: "polyline", coordinates: "[...]", ...}
  1: {id: 2, name: "test", type: "polyline", coordinates: "[...]", ...}
  ...
Total layers: 5
Current layer IDs: []
New layer IDs: [1, 2, 3, 4, 5]
Creating layer: 1 test
Layer added to map: 1
Creating layer: 2 test
Layer added to map: 2
Creating layer: 3 kabel 12 core
Layer added to map: 3
Creating layer: 4 dgfgd
Layer added to map: 4
Creating layer: 5 www
Layer added to map: 5
Total layers in layerObjects: 5
Layers on map: 5
```

---

## ⚠️ Common Errors

### Error: "Unexpected token < in JSON"
**Cause:** API returning HTML instead of JSON (redirect/error page)

**Fix:** Check route middleware, ensure user authenticated

### Error: "Cannot read property 'addLayer' of undefined"
**Cause:** `savedLayersGroup` not initialized

**Fix:** Check line order - ensure `savedLayersGroup` created before `loadSavedLayers()`

### Error: "Layer tidak muncul tapi console logs OK"
**Cause:** Checkbox "Tampilkan Layer Tersimpan" unchecked

**Fix:** 
1. Cek panel kanan atas
2. Pastikan checkbox "Tampilkan Layer Tersimpan" CHECKED

---

## 📞 How to Report Issue

Jika masih bermasalah, screenshot dan kirim:

1. **Console logs** (full output)
2. **Network tab** → Request ke `/map/layers` → Response
3. **Database query result:**
   ```bash
   mysql -u root -p'123456789' -e "USE kencana; SELECT id, name FROM map_layers;"
   ```

---

## 🚀 Quick Fix Commands

Clear semua cache:
```bash
cd /var/www/kencana.alus.co.id
php artisan cache:clear
php artisan view:clear
php artisan config:clear
php artisan route:clear
```

Restart services:
```bash
systemctl restart php7.4-fpm
systemctl restart nginx
```

---

**Created:** 2026-01-28  
**Status:** Debugging Tools Added
**Next Step:** Check browser console logs
