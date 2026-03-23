# Sales Portal Guide

## Overview
Portal Sales memungkinkan tim sales untuk login dan melihat daftar customer yang mereka handle. Setiap sales dapat:
- Login dengan email dan password
- Melihat dashboard dengan statistik customer mereka
- Melihat daftar lengkap customer yang terdaftar dengan id_sale mereka
- Melihat detail setiap customer
- Logout dari sistem

## URL Akses
- **Login**: `https://kencana.alus.co.id/sales/login`
- **Dashboard**: `https://kencana.alus.co.id/sales`
- **Aktivasi Akun**: `https://kencana.alus.co.id/sales/activate`

## Fitur Utama

### 1. Aktivasi Akun (First Time Setup)
Sales yang baru ditambahkan ke sistem perlu mengaktifkan akun mereka terlebih dahulu:
1. Akses `/sales/activate`
2. Masukkan:
   - Email yang terdaftar di sistem
   - Nomor telepon yang terdaftar
   - Password baru (minimal 6 karakter)
   - Konfirmasi password
3. Sistem akan memvalidasi email dan phone
4. Password akan di-hash dan disimpan ke database
5. Sales dapat langsung login setelah aktivasi

### 2. Login
1. Akses `/sales/login`
2. Masukkan email dan password
3. Centang "Ingat Saya" untuk tetap login (opsional)
4. Klik Login
5. Sistem akan redirect ke dashboard

**Catatan**: Last login timestamp akan diupdate setiap kali sales berhasil login.

### 3. Dashboard
Dashboard menampilkan:
- **Header**: Nama sales dan tombol logout
- **Welcome Card**: 
  - Nama lengkap sales
  - Email dan nomor telepon
  - Waktu login terakhir
- **Statistik Card**:
  - Total Pelanggan
  - Pelanggan Aktif (status = 2)
  - Pelanggan Block (status = 4)
  - Pelanggan Inactive (status = 3)
- **Tabel Customer**:
  - No urut
  - CID (Customer ID)
  - Nama
  - Alamat (dipotong 30 karakter)
  - Telepon
  - Paket internet (nama dan harga)
  - Status dengan badge berwarna
  - Tombol "Detail" untuk melihat informasi lengkap

### 4. Detail Customer
Menampilkan informasi lengkap customer:
- Customer ID (CID)
- Status (dengan badge warna)
- Nama lengkap
- Email
- Nomor telepon
- Paket internet (nama dan harga)
- Alamat lengkap
- Tanggal mulai billing
- Username PPPoE
- Router (jika ada)
- Merchant (jika ada)
- Koordinat (jika ada)

**Keamanan**: Sales hanya bisa melihat customer yang memiliki `id_sale` yang sama dengan ID mereka. Jika mencoba akses customer lain, akan muncul error 404.

### 5. Logout
- Klik tombol Logout di header
- Session sales akan dihapus
- Redirect ke halaman login

## Implementasi Teknis

### Database
**Table**: `sales`
- `id`: Primary key
- `name`: Nama sales (username)
- `full_name`: Nama lengkap
- `email`: Email (unique, untuk login)
- `password`: Password (hashed)
- `phone`: Nomor telepon
- `remember_token`: Token untuk "remember me"
- `last_login_at`: Timestamp login terakhir
- Other fields: job_title, sale_type, join_date, address, photo, etc.

**Table**: `customers`
- `id_sale`: Foreign key ke sales.id
- Kolom lain: customer_id, name, address, phone, email, id_status, id_plan, etc.

### Authentication Guard
```php
// config/auth.php
'guards' => [
    'sales' => [
        'driver' => 'session',
        'provider' => 'sales',
    ],
],

'providers' => [
    'sales' => [
        'driver' => 'eloquent',
        'model' => App\Sale::class,
    ],
],
```

### Model
**Sale Model** (`app/Sale.php`):
- Extends `Illuminate\Foundation\Auth\User as Authenticatable`
- Uses `Notifiable` dan `SoftDeletes` traits
- Hidden fields: `password`, `remember_token`
- Relationship: `hasMany` dengan Customer melalui `id_sale`

### Controller
**SalesAuthController** (`app/Http/Controllers/SalesAuthController.php`):
- `index()`: Dashboard dengan customer list
- `showLogin()`: Form login
- `login()`: Proses login dan update last_login_at
- `logout()`: Logout dan clear session
- `showCustomer($id)`: Detail customer (dengan validasi ownership)
- `showActivate()`: Form aktivasi
- `activate()`: Set password pertama kali

### Routes
```php
Route::prefix('sales')->group(function() {
    // Public routes
    Route::get('/login', [SalesAuthController::class, 'showLogin']);
    Route::post('/login', [SalesAuthController::class, 'login']);
    Route::get('/activate', [SalesAuthController::class, 'showActivate']);
    Route::post('/activate', [SalesAuthController::class, 'activate']);
    
    // Protected routes (auth:sales middleware)
    Route::middleware('auth:sales')->group(function() {
        Route::get('/', [SalesAuthController::class, 'index']);
        Route::get('/customer/{id}', [SalesAuthController::class, 'showCustomer']);
        Route::get('/logout', [SalesAuthController::class, 'logout']);
    });
});
```

### Views
1. **resources/views/sales/login.blade.php**: Halaman login dengan gradient pink
2. **resources/views/sales/activate.blade.php**: Form aktivasi akun
3. **resources/views/sales/dashboard.blade.php**: Dashboard dengan statistik dan tabel customer
4. **resources/views/sales/customer-detail.blade.php**: Detail customer lengkap

### Status Customer
Status ditampilkan dengan badge berwarna berdasarkan `id_status`:
- **1 - Potensial**: #3bacd9 (biru muda)
- **2 - Active**: #2bd93a (hijau)
- **3 - Inactive**: #959c9a (abu-abu)
- **4 - Block**: #e32510 (merah)
- **5 - Company_Properti**: #8866aa (ungu)

## Cara Menggunakan

### Untuk Sales Baru
1. Admin menambahkan data sales baru di sistem dengan email dan phone
2. Sales mengakses `/sales/activate`
3. Masukkan email dan phone yang sudah didaftarkan
4. Set password baru
5. Login dengan email dan password yang baru dibuat

### Untuk Sales yang Sudah Ada
1. Akses `/sales/login`
2. Login dengan email dan password
3. Dashboard akan menampilkan semua customer yang memiliki `id_sale` sesuai dengan ID sales yang login

## Security Features
- Password di-hash menggunakan `Hash::make()`
- Validasi ownership customer (sales hanya bisa lihat customer mereka)
- Session-based authentication dengan guard 'sales'
- Remember me token untuk persistent login
- Last login tracking untuk audit

## Troubleshooting

### Sales tidak bisa login
- Pastikan sales sudah aktivasi akun (set password)
- Cek email dan password yang dimasukkan
- Pastikan data sales ada di database dengan email yang benar

### Customer tidak muncul di dashboard
- Pastikan customer memiliki `id_sale` yang sesuai dengan ID sales
- Cek apakah relasi di Customer model sudah benar

### Error 404 saat akses detail customer
- Sales mencoba akses customer yang bukan miliknya
- Sistem akan return 404 jika `id_sale` customer tidak match dengan sales yang login

## Future Enhancements (Opsional)
- Forgot password functionality
- Filter dan search customer di dashboard
- Export customer list to Excel/PDF
- Grafik perkembangan customer
- Notifikasi untuk customer baru
- Update profile sales
- Ganti password

## Testing
1. **Aktivasi**:
   ```
   Email: office@twinnet.trikamedia.com
   Phone: 6281805360534
   Password: 123456
   ```

2. **Login**: Gunakan email dan password yang sudah di-set saat aktivasi

3. **Dashboard**: Akan menampilkan customer dengan `id_sale = 1`

4. **Detail**: Klik tombol "Detail" pada salah satu customer
