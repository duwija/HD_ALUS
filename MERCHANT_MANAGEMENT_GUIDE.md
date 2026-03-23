# 📋 Merchant Management Guide

Panduan lengkap untuk mengelola Merchant (Mitra/Reseller) dalam sistem ISP Multi-Tenant.

---

## 📑 Daftar Isi

1. [Overview](#overview)
2. [Database Structure](#database-structure)
3. [Akses Halaman Merchant](#akses-halaman-merchant)
4. [Menambah Merchant Baru](#menambah-merchant-baru)
5. [Melihat Detail Merchant](#melihat-detail-merchant)
6. [Mengubah Data Merchant](#mengubah-data-merchant)
7. [Menghapus Merchant](#menghapus-merchant)
8. [Integrasi dengan Customer](#integrasi-dengan-customer)
9. [Integrasi dengan Accounting](#integrasi-dengan-accounting)
10. [API Endpoints](#api-endpoints)
11. [Troubleshooting](#troubleshooting)

---

## 🎯 Overview

**Merchant** adalah mitra/reseller yang bekerja sama dengan ISP untuk menjual layanan internet. Setiap merchant dapat memiliki:
- Data kontak lengkap
- Lokasi koordinat (Google Maps)
- Akun accounting untuk tracking pembayaran
- Payment point untuk komisi
- Daftar customer yang terdaftar melalui merchant tersebut

### Fitur Utama:
✅ CRUD lengkap (Create, Read, Update, Delete)  
✅ Integrasi Google Maps untuk lokasi  
✅ Link ke Customer Management  
✅ Link ke Accounting System  
✅ DataTables dengan search & sorting  
✅ Soft Delete (data bisa dipulihkan)  

---

## 🗄️ Database Structure

### Tabel: `merchants`

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | bigint (PK) | NO | AUTO_INCREMENT | Primary Key |
| `name` | varchar(255) | NO | - | Nama merchant/perusahaan |
| `contact_name` | varchar(255) | NO | - | Nama contact person |
| `phone` | varchar(50) | NO | - | Nomor telepon |
| `address` | text | YES | NULL | Alamat lengkap |
| `coordinate` | varchar(100) | YES | NULL | Lat,Lng untuk Google Maps |
| `description` | text | YES | NULL | Deskripsi/catatan tambahan |
| `akun_code` | varchar(20) | YES | NULL | Kode akun accounting (FK) |
| `payment_point` | decimal(10,2) | YES | 0.00 | Point/komisi pembayaran |
| `created_at` | timestamp | YES | CURRENT_TIMESTAMP | Tanggal dibuat |
| `updated_at` | timestamp | YES | NULL | Tanggal update terakhir |
| `deleted_at` | timestamp | YES | NULL | Soft delete timestamp |

### Relationships:
- `merchants.akun_code` → `akuns.akun_code` (belongsTo)
- `merchants.id` → `customers.id_merchant` (hasMany)

---

## 🌐 Akses Halaman Merchant

### URL Routes:

```php
// List semua merchant
GET /merchant

// Form tambah merchant baru
GET /merchant/create

// Submit merchant baru
POST /merchant

// Detail merchant
GET /merchant/{id}

// Form edit merchant
GET /merchant/{id}/edit

// Update merchant
PATCH /merchant/{id}

// Hapus merchant
DELETE /merchant/{id}

// API: DataTables
POST /merchant/table_merchant_list

// API: Get merchant info
GET /merchant/getmerchantinfo/{id}

// API: Get total akun
GET /gettotalakun/{akun_code}
```

### Menu Navigation:
Biasanya berada di sidebar menu:
```
📊 Master Data
  └── 🏢 Merchant
```

---

## ➕ Menambah Merchant Baru

### Step-by-Step:

#### 1. Akses Form Create
```
Klik menu: Master Data → Merchant → Tambah Baru
atau langsung: http://domain.com/merchant/create
```

#### 2. Isi Form Data Merchant

**Data Wajib:**
- ✅ **Nama Merchant** - Nama perusahaan/mitra
- ✅ **Contact Name** - Nama PIC (Person In Charge)
- ✅ **Phone** - Nomor telepon (angka saja)

**Data Optional:**
- 📍 **Address** - Alamat lengkap
- 🗺️ **Coordinate** - Pilih lokasi di peta (Google Maps)
- 📝 **Description** - Catatan tambahan
- 💰 **Akun Code** - Pilih akun accounting (Kas & Bank)
- 💵 **Payment Point** - Komisi per transaksi

#### 3. Set Lokasi di Google Maps

Form create dilengkapi Google Maps interaktif:
```javascript
// Marker bisa di-drag untuk update koordinat
// Koordinat otomatis tersimpan saat marker dipindah
// Format: "latitude,longitude"
// Contoh: "-6.200000,106.816666"
```

**Cara menggunakan peta:**
1. Zoom in ke lokasi yang diinginkan
2. Drag marker (pin merah) ke lokasi merchant
3. Koordinat otomatis ter-update
4. Simpan form

#### 4. Submit Form

Klik tombol **"Simpan"** atau **"Submit"**

**Validation Rules:**
```php
'name' => 'required|string|max:255',
'contact_name' => 'required|string|max:255',
'phone' => 'required|numeric',
'address' => 'nullable|string',
'coordinate' => 'nullable|string',
'description' => 'nullable|string',
'akun_code' => 'nullable|exists:akuns,akun_code',
'payment_point' => 'nullable|numeric|min:0'
```

#### 5. Response

✅ **Success:**
```
Redirect ke: /merchant
Flash message: "Item created successfully!"
```

❌ **Error:**
```
Redirect back dengan error message
Form tetap ter-isi (old input)
```

---

## 👁️ Melihat Detail Merchant

### Akses Detail Page

```
GET /merchant/{id}
```

### Informasi yang Ditampilkan:

#### Tab 1: Informasi Merchant
- Nama merchant
- Contact person
- Nomor telepon
- Alamat lengkap
- Deskripsi
- Akun accounting
- Payment point
- Tanggal dibuat

#### Tab 2: Google Maps
- Peta lokasi merchant
- Marker di koordinat yang tersimpan
- Info window dengan nama merchant

#### Tab 3: Daftar Customer
- Tabel customer yang terdaftar via merchant ini
- Filter by status (Active/Inactive)
- Filter by plan
- Total customer
- Total revenue
- Link ke detail customer

#### Tab 4: Accounting Summary
Jika merchant punya `akun_code`:
- Total Debet
- Total Kredit
- Saldo (Debet - Kredit)
- History transaksi

---

## ✏️ Mengubah Data Merchant

### Step-by-Step:

#### 1. Akses Form Edit
```
Dari halaman list: Klik tombol Edit (ikon pensil)
atau langsung: GET /merchant/{id}/edit
```

#### 2. Form Pre-filled
Form akan ter-isi dengan data saat ini:
```php
value="{{ old('name', $merchant->name) }}"
value="{{ old('phone', $merchant->phone) }}"
// dst...
```

#### 3. Ubah Data yang Diperlukan
- Semua field bisa diubah
- Koordinat bisa diupdate via drag marker
- Validation sama seperti create

#### 4. Submit Update
```
Method: PATCH /merchant/{id}
```

#### 5. Response

✅ **Success:**
```
Redirect ke: /merchant/{id} (detail page)
Flash message: "Merchant updated successfully!"
```

---

## 🗑️ Menghapus Merchant

### Soft Delete

Sistem menggunakan **Soft Delete**, jadi data tidak benar-benar hilang dari database.

#### Cara Hapus:

**1. Via List Page:**
```
Klik tombol Delete (ikon trash)
→ Konfirmasi popup
→ Submit form DELETE
```

**2. Via API:**
```javascript
fetch('/merchant/' + merchantId, {
    method: 'DELETE',
    headers: {
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json'
    }
})
```

#### Response:
```
Redirect ke: /merchant
Flash message: "Merchant deleted successfully!"
```

#### Data di Database:
```sql
-- Kolom deleted_at akan terisi timestamp
UPDATE merchants 
SET deleted_at = NOW() 
WHERE id = {id};
```

### Restore Merchant

Untuk restore merchant yang sudah dihapus:
```php
// Di Laravel Tinker atau Controller
$merchant = Merchant::withTrashed()->find($id);
$merchant->restore();
```

### Permanent Delete

Untuk hapus permanen (tidak recommended):
```php
$merchant = Merchant::withTrashed()->find($id);
$merchant->forceDelete();
```

---

## 👥 Integrasi dengan Customer

### Relationship

Setiap Customer bisa punya merchant:
```php
// Di Customer Model
public function merchant() {
    return $this->belongsTo(Merchant::class, 'id_merchant');
}

// Di Merchant Model
public function customers() {
    return $this->hasMany(Customer::class, 'id_merchant');
}
```

### Use Cases:

#### 1. Lihat Customer by Merchant
```php
$merchant = Merchant::find(1);
$customers = $merchant->customers;

// Atau dengan filter
$activeCustomers = $merchant->customers()
    ->where('status', 'active')
    ->get();
```

#### 2. Assign Customer ke Merchant
```php
// Saat create customer
Customer::create([
    'name' => 'John Doe',
    'id_merchant' => 1, // ID merchant
    // ... fields lain
]);

// Atau update existing customer
$customer = Customer::find(123);
$customer->id_merchant = 1;
$customer->save();
```

#### 3. Filter Customer by Merchant
```
URL: /customermerchant
Menampilkan customer yang punya id_merchant
```

---

## 💰 Integrasi dengan Accounting

### Akun Merchant

Merchant bisa di-link ke akun accounting (kategori: Kas & Bank):

#### 1. Set Akun saat Create/Edit Merchant
```php
// Dropdown akun hanya menampilkan kategori "Kas & Bank"
$akuns = Akun::whereNotIn('akun_code', $parentAkuns)
    ->where('category', 'kas & bank')
    ->get();
```

#### 2. Get Total Saldo Akun
```javascript
// API Call
GET /gettotalakun/{akun_code}

// Response:
{
    "success": true,
    "total": 15000000,
    "total_debet": 20000000,
    "total_kredit": 5000000
}
```

#### 3. Payment Point

Komisi/point yang didapat merchant per transaksi:
```php
$merchant->payment_point = 50000; // Rp 50.000 per transaksi
```

**Use Case:**
- Tracking komisi merchant
- Laporan penjualan per merchant
- Perhitungan bonus/insentif

---

## 🔌 API Endpoints

### 1. DataTables List

**Endpoint:**
```
POST /merchant/table_merchant_list
```

**Response:**
```json
{
    "draw": 1,
    "recordsTotal": 50,
    "recordsFiltered": 50,
    "data": [
        {
            "id": 1,
            "name": "<a href='/merchant/1' class='badge badge-primary'>PT Mitra Jaya</a>",
            "contact_name": "Budi Santoso",
            "phone": "081234567890",
            "address": "Jl. Sudirman No. 123"
        }
    ]
}
```

**Features:**
- Server-side processing
- Search semua kolom
- Sortable columns
- Pagination

### 2. Get Merchant Info

**Endpoint:**
```
GET /merchant/getmerchantinfo/{id}
```

**Response:**
```json
{
    "id": 1,
    "name": "PT Mitra Jaya",
    "contact_name": "Budi Santoso",
    "phone": "081234567890",
    "address": "Jl. Sudirman No. 123",
    "coordinate": "-6.200000,106.816666",
    "description": "Merchant premium area Jakarta",
    "akun_code": "1-10001",
    "payment_point": 50000,
    "created_at": "2025-01-15T10:30:00.000000Z"
}
```

### 3. Get Total Akun

**Endpoint:**
```
GET /gettotalakun/{akun_code}
```

**Parameters:**
- `akun_code` - Kode akun (contoh: "1-10001")

**Response:**
```json
{
    "success": true,
    "total": 15000000,
    "total_debet": 20000000,
    "total_kredit": 5000000
}
```

**Logic:**
```sql
SELECT 
    SUM(debet) as total_debet,
    SUM(kredit) as total_kredit
FROM jurnals
WHERE id_akun = {akun_code}

-- Total = total_debet - total_kredit
```

---

## 🛠️ Troubleshooting

### Problem 1: Google Maps Tidak Muncul

**Symptom:**
Halaman create/edit merchant, peta tidak ter-load.

**Cause:**
- Google Maps API key tidak valid
- API key tidak punya akses Maps JavaScript API
- ENV variable tidak ter-set

**Solution:**
```bash
# Check .env atau database env_variables
GOOGLE_MAPS_API_KEY=your_api_key_here

# Pastikan API enabled di Google Cloud Console:
# - Maps JavaScript API
# - Geocoding API (optional)
```

### Problem 2: Koordinat Tidak Tersimpan

**Symptom:**
Setelah drag marker, koordinat tidak berubah di database.

**Cause:**
JavaScript callback `updateDatabase()` tidak berjalan.

**Solution:**
```javascript
// Check di view create/edit merchant
function updateDatabase(lat, lng) {
    document.getElementById('coordinate').value = lat + ',' + lng;
    console.log('Coordinate updated:', lat, lng);
}

// Pastikan ada input hidden:
<input type="hidden" name="coordinate" id="coordinate" value="">
```

### Problem 3: Validation Error Phone

**Symptom:**
Error: "The phone must be a number"

**Cause:**
Phone field diisi dengan format: "(021) 123-4567"

**Solution:**
```
Isi hanya dengan angka: 0211234567
Tidak boleh ada:
- Spasi
- Tanda kurung ()
- Tanda strip -
- Karakter non-numeric
```

### Problem 4: Akun Dropdown Kosong

**Symptom:**
Dropdown akun accounting tidak menampilkan pilihan.

**Cause:**
- Belum ada akun kategori "Kas & Bank"
- Query filter terlalu strict

**Solution:**
```php
// Cek di database
SELECT * FROM akuns WHERE category = 'kas & bank';

// Jika kosong, tambah akun dulu:
INSERT INTO akuns (akun_code, akun_name, category) 
VALUES ('1-10001', 'Bank BCA - Merchant', 'kas & bank');
```

### Problem 5: Error saat Delete

**Symptom:**
"Cannot delete merchant, has related customers"

**Cause:**
Merchant punya customer yang masih aktif (foreign key constraint).

**Solution:**
```
Option 1: Update customer dulu
- Set id_merchant = NULL untuk semua customer
- Atau assign ke merchant lain

Option 2: Cascade delete (tidak recommended)
- Hapus semua customer merchant tersebut
```

### Problem 6: Payment Point Tidak Muncul

**Symptom:**
Kolom payment_point tidak tersimpan/tidak muncul.

**Cause:**
Field tidak ada di `$fillable` array.

**Solution:**
```php
// Di app/Merchant.php
protected $fillable = [
    'name', 
    'contact_name', 
    'phone', 
    'address', 
    'coordinate', 
    'description',
    'created_at',
    'akun_code',
    'payment_point' // Pastikan ada
];
```

---

## 📊 Best Practices

### 1. Naming Convention
```
✅ Good:
- PT Mitra Internet Jakarta
- CV Reseller Network Surabaya
- UD Teknologi Bandung

❌ Bad:
- merchant1
- test
- aaa
```

### 2. Contact Person
```
Isi dengan nama lengkap + jabatan:
✅ "Budi Santoso - Director"
✅ "Ani Wulandari - Sales Manager"
```

### 3. Phone Format
```
✅ Good: 081234567890
✅ Good: 02112345678

❌ Bad: (021) 123-4567
❌ Bad: +62 812 3456 7890
```

### 4. Coordinate Precision
```
✅ Good: "-6.200123,106.816789" (6 decimal)
❌ Bad: "-6.2,106.8" (terlalu kasar)
```

### 5. Akun Accounting
```
Buat akun khusus per merchant untuk tracking:
- 1-10001: Bank BCA - Merchant A
- 1-10002: Bank Mandiri - Merchant B
```

---

## 📈 Reporting & Analytics

### Query Examples:

#### 1. Top 10 Merchant by Customer Count
```sql
SELECT 
    m.name,
    COUNT(c.id) as total_customers,
    SUM(CASE WHEN c.status = 'active' THEN 1 ELSE 0 END) as active_customers
FROM merchants m
LEFT JOIN customers c ON m.id = c.id_merchant
WHERE m.deleted_at IS NULL
GROUP BY m.id, m.name
ORDER BY total_customers DESC
LIMIT 10;
```

#### 2. Merchant Revenue Summary
```sql
SELECT 
    m.name as merchant_name,
    COUNT(DISTINCT c.id) as total_customers,
    SUM(i.total) as total_revenue,
    AVG(i.total) as avg_invoice
FROM merchants m
LEFT JOIN customers c ON m.id = c.id_merchant
LEFT JOIN invoices i ON c.id = i.customer_id
WHERE m.deleted_at IS NULL
  AND i.status = 'paid'
GROUP BY m.id, m.name
ORDER BY total_revenue DESC;
```

#### 3. Merchant Without Customers
```sql
SELECT 
    m.id,
    m.name,
    m.contact_name,
    m.phone,
    m.created_at
FROM merchants m
LEFT JOIN customers c ON m.id = c.id_merchant
WHERE m.deleted_at IS NULL
  AND c.id IS NULL
ORDER BY m.created_at DESC;
```

---

## 🔐 Security & Permissions

### Recommendations:

#### 1. Role-Based Access
```php
// Middleware untuk protect merchant routes
Route::middleware(['auth', 'role:admin,manager'])->group(function () {
    Route::resource('merchant', 'MerchantController');
});
```

#### 2. Audit Log
Track perubahan data merchant:
```php
// Create audit log saat create/update/delete
AuditLog::create([
    'user_id' => auth()->id(),
    'action' => 'update',
    'model' => 'Merchant',
    'model_id' => $merchant->id,
    'old_values' => json_encode($oldData),
    'new_values' => json_encode($newData),
]);
```

#### 3. Soft Delete Policy
```php
// Hanya super admin yang bisa force delete
if (auth()->user()->role !== 'super_admin') {
    // Hanya soft delete
    $merchant->delete();
} else {
    // Force delete allowed
    $merchant->forceDelete();
}
```

---

## 📱 WhatsApp Provider Configuration

### Overview

Sistem mendukung 2 provider WhatsApp untuk notifikasi:
1. **WA Gateway** - Gateway WhatsApp biasa (default)
2. **Qontak** - WhatsApp Business API resmi via Qontak

Merchant dapat memilih provider WhatsApp yang diinginkan melalui tenant environment variables.

### Configuration

#### Setting Provider via Tenant Config

Tambahkan di **Custom Environment Variables** tenant:

**Untuk WA Gateway (Default):**
```
Key: wa_provider
Value: gateway
```

**Untuk Qontak:**
```
Key: wa_provider
Value: qontak
```

#### Required Variables per Provider

**1. WA Gateway Variables:**
```
WA_GATEWAY_URL          - URL endpoint WA Gateway
WA_GROUP_PAYMENT        - Group ID untuk notifikasi payment
WA_GROUP_SUPPORT        - Group ID untuk notifikasi support
payment_wa              - Nomor WhatsApp customer service
domain_name             - Domain untuk link invoice
```

**2. Qontak Variables (jika pakai qontak):**
```
ACCESS_TOKEN                  - Bearer token dari Qontak
WHATSAPP_API_URL             - API endpoint Qontak
WA_CHANNEL_INTEGRATION_ID    - Channel Integration ID
WA_TAMPLATE_ID_4             - Template ID untuk reminder invoice
WAPISENDER_STATUS            - enable/disable (untuk enable Qontak)
```

---

## 🔍 Network Monitoring (Probe) Configuration

### Overview

Sistem memiliki fitur **Network Probe Monitoring** untuk mengawasi status uptime server/device secara real-time. Setiap tenant dapat memiliki **probe_key** sendiri untuk keamanan.

### Configuration

#### Setting Probe Key via Tenant Config

Tambahkan di **Custom Environment Variables** tenant:

```
Key: probe_key
Value: your-secret-probe-key-here
```

**Contoh:**
```
Key: probe_key
Value: aB3xD9mK2pQ7wE1rT5yU8iO4sA6fG0hJ
```

### How It Works

1. **External Probe** mengirim ping result ke endpoint `/api/probe/push`
2. **Header Authorization**: `X-Probe-Key: your-secret-probe-key-here`
3. System validasi key:
   - Cek tenant `probe_key` dari database
   - Jika tidak ada, fallback ke `.env` (`PROBE_KEY`)
   - Jika match → data disimpan
   - Jika tidak match → `401 Unauthorized`

### Endpoint API

**Push Ping Result:**
```
POST /api/probe/push
Headers:
  X-Probe-Key: your-secret-probe-key-here
  
Body (JSON or Form):
{
  "probe_id": "probe-01",
  "host": "192.168.1.1",
  "host_name": "Gateway Router",
  "status": "up",
  "rtt": 12.5
}
```

**Dashboard:**
```
GET /probe
```

### Security Best Practices

1. **Generate Strong Key**: Minimal 32 karakter random
2. **Unique per Tenant**: Jangan gunakan key yang sama untuk semua tenant
3. **Rotate Regularly**: Ganti key secara berkala untuk keamanan
4. **Use HTTPS**: Pastikan endpoint menggunakan HTTPS di production

### Example cURL

```bash
curl -X POST https://yourdomain.com/api/probe/push \
  -H "X-Probe-Key: aB3xD9mK2pQ7wE1rT5yU8iO4sA6fG0hJ" \
  -H "Content-Type: application/json" \
  -d '{
    "probe_id": "probe-jakarta",
    "host": "103.123.45.67",
    "host_name": "Core Router JKT",
    "status": "up",
    "rtt": 8.3
  }'
```

---

## 📡 How It Works

Saat sistem mengirim notifikasi WhatsApp (misalnya reminder invoice):

1. **Cek Provider** - System cek `wa_provider` dari tenant config
2. **Route ke Provider**:
   - Jika `qontak` → Gunakan Qontak WhatsApp Business API
   - Jika `gateway` atau tidak diset → Gunakan WA Gateway biasa

**Code Implementation:**
```php
// Di app/Jobs/NotifInvJob.php
$waProvider = tenant_config('wa_provider', 'gateway'); // default: gateway

if ($waProvider === 'qontak') {
    // Use Qontak WhatsApp API
    $response = qontak_whatsapp_helper_job_remainder_inv(
        $phone,
        $name,
        $customer_id,
        $invoice_url
    );
} else {
    // Use Regular WA Gateway
    $message = "...[format pesan]...";
    $msgresult = WaGatewayHelper::wa_payment($phone, $message);
}
```

### Message Format

#### WA Gateway (Custom Format):
```
*[Pengingat Pembayaran Internet]*

Pelanggan Yth. 

Nama : John Doe
CID : CUST001
Kami ingin mengingatkan bahwa tagihan Anda sudah tersedia
Agar tetap bisa menikmati Layanan kami, mohon untuk menyelesaikan pembayaran tepat waktu.

Untuk informasi lebih lanjut, silakan klik link berikut:
http://domain.com/invoice/xxx

Jika sudah melakukan pembayaran, abaikan pesan ini.
Jika ada pertanyaan, hubungi CS kami di 08123456789

PT Internet Service Provider
```

#### Qontak (Template-based):
Menggunakan template yang sudah disetup di dashboard Qontak dengan parameter:
- `name` - Nama customer
- `customer_id` - Customer ID
- `url` - Link invoice (sebagai button)

### Setup Guide

#### Step 1: Pilih Provider
Di halaman **Edit Tenant**, scroll ke **Custom Environment Variables**

#### Step 2: Tambah Variable
Klik **Show/Hide** untuk melihat daftar variable available

#### Step 3: Set Provider
Tambahkan:
```
Key: wa_provider
Value: gateway  (atau qontak)
```

#### Step 4: Tambah Credentials
Sesuai provider yang dipilih, tambahkan variable yang required

#### Step 5: Test
Kirim test notifikasi untuk memastikan provider berjalan dengan baik

### Switching Provider

Untuk pindah provider:

**Dari Gateway ke Qontak:**
1. Update `wa_provider` → `qontak`
2. Tambahkan semua Qontak credentials
3. Set `WAPISENDER_STATUS` → `enable`
4. Test sending

**Dari Qontak ke Gateway:**
1. Update `wa_provider` → `gateway`
2. Pastikan WA_GATEWAY_URL sudah diset
3. Test sending

### Troubleshooting

#### Problem: Notifikasi tidak terkirim

**Check:**
1. Nilai `wa_provider` di env_variables (pastikan lowercase)
2. Credentials provider yang dipilih sudah lengkap
3. Log di `storage/logs/notif.log`

**Debug:**
```php
// Check tenant config
$provider = tenant_config('wa_provider');
dd($provider); // Should be 'gateway' or 'qontak'
```

#### Problem: Qontak return error

**Common Causes:**
- `ACCESS_TOKEN` expired → Refresh token di Qontak dashboard
- `WA_TAMPLATE_ID_4` salah → Cek template ID di Qontak
- `WA_CHANNEL_INTEGRATION_ID` tidak valid → Verifikasi channel

**Check Response:**
```php
// Di helper function qontak_whatsapp_helper_job_remainder_inv
Log::info('Qontak Response:', $response);
```

#### Problem: WA Gateway tidak berfungsi

**Check:**
- `WA_GATEWAY_URL` accessible (ping/curl test)
- Gateway service running
- Nomor pengirim masih aktif

### Best Practices

#### 1. Default Fallback
Selalu set provider default di `.env` global:
```env
WA_PROVIDER=gateway
```

#### 2. Separate Credentials
Simpan credentials setiap provider terpisah per tenant untuk security

#### 3. Log Everything
Enable logging untuk debug:
```php
Log::channel('notif')->info('WA Provider: ' . $waProvider);
Log::channel('notif')->info('Send to: ' . $phone);
```

#### 4. Template Management
Untuk Qontak:
- Buat template berbeda per jenis notifikasi
- Store template ID di env_variables
- Contoh: `WA_TEMPLATE_REMINDER`, `WA_TEMPLATE_PAYMENT`, dll

#### 5. Cost Monitoring
- WA Gateway: biaya per pesan tergantung provider
- Qontak: biaya conversation-based
- Monitor usage per tenant

### Related Configuration

**Tenant ENV Variables Reference:**
```
# WhatsApp Provider Selection
wa_provider = gateway | qontak

# WA Gateway Config
WA_GATEWAY_URL
WA_GROUP_PAYMENT
WA_GROUP_SUPPORT
payment_wa
domain_name

# Qontak Config
ACCESS_TOKEN
WHATSAPP_API_URL
WA_CHANNEL_INTEGRATION_ID
WA_TAMPLATE_ID_4
WAPISENDER_STATUS = enable | disable
```

---

## 📝 Summary

**Merchant Management** adalah modul untuk mengelola mitra/reseller ISP dengan fitur:

✅ **CRUD Lengkap** - Create, Read, Update, Delete (soft)  
✅ **Google Maps Integration** - Tracking lokasi merchant  
✅ **Customer Linking** - Hubungkan customer ke merchant  
✅ **Accounting Integration** - Link ke akun kas/bank  
✅ **Payment Points** - Tracking komisi merchant  
✅ **DataTables** - List dengan search & sort  
✅ **API Ready** - REST endpoints untuk integrasi  

**Key Models:**
- `Merchant` - Model utama
- `Customer` - Relationship hasMany
- `Akun` - Relationship belongsTo

**Important Routes:**
```
GET  /merchant          → List
GET  /merchant/create   → Form create
POST /merchant          → Store
GET  /merchant/{id}     → Detail
GET  /merchant/{id}/edit → Form edit
PATCH /merchant/{id}    → Update
DELETE /merchant/{id}   → Delete (soft)
```

---

**Dibuat:** November 22, 2025  
**Untuk:** ISP Multi-Tenant System  
**Versi:** 1.0  

---

## 🆘 Need Help?

Jika ada pertanyaan atau issue, contact:
- Technical Support: support@alus.co.id
- Developer: dev@alus.co.id

**Related Guides:**
- [Customer Management Guide](CUSTOMER_MANAGEMENT_GUIDE.md)
- [Accounting Guide](ACCOUNTING_GUIDE.md)
- [Admin User Management Guide](ADMIN_USER_MANAGEMENT_GUIDE.md)
