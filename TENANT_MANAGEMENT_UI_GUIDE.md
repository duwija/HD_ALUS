# Panduan Tenant Management UI

## Overview
Sistem tenant management UI menyediakan interface web untuk mengelola multi-tenant tanpa perlu akses SSH atau CLI. Admin dapat menambah, mengedit, melihat detail, dan menonaktifkan tenant melalui browser.

## Akses

### URL
```
https://[DOMAIN]/admin/tenants
```

Contoh:
- https://kencana.alus.co.id/admin/tenants
- https://adiyasa.alus.co.id/admin/tenants

### Autentikasi
- Memerlukan login terlebih dahulu
- Protected dengan middleware `auth`
- Hanya user yang sudah terdaftar dan login yang bisa mengakses

## Fitur Utama

### 1. Daftar Tenant (Index)
**URL:** `/admin/tenants`

Menampilkan tabel dengan informasi:
- Domain
- App Name
- Rescode (badge)
- Database
- Features (badges)
- Status (Active/Inactive dengan toggle)
- Actions (View, Edit, Delete)

**Fitur:**
- Toggle status Active/Inactive tanpa refresh halaman (AJAX)
- Filter dan pencarian (jika diperlukan)
- Konfirmasi sebelum menghapus tenant

### 2. Tambah Tenant Baru (Create)
**URL:** `/admin/tenants/create`

**Form Input:**

#### Informasi Umum
- **Domain** (required): `contoh.alus.co.id`
- **App Name** (required): `PT CONTOH INTERNET`
- **Signature**: Signature email
- **Rescode** (required): Kode unik (huruf kecil, underscore)
- **Email From** (required): Email pengirim notifikasi

#### Konfigurasi Database
- **DB Host** (required): Default `localhost`
- **DB Port** (required): Default `3306`
- **DB Name** (required): Nama database tenant
- **DB Username** (required): Username database
- **DB Password** (required): Password database
- **Auto Create Database**: Checkbox untuk membuat database otomatis

#### Features
- ☑ Accounting
- ☑ Ticketing
- ☑ WhatsApp Integration
- ☑ Payment Gateway

#### Catatan
- Field untuk catatan tambahan

**Proses Otomatis Saat Submit:**
1. Validasi input
2. Buat database jika checkbox active (dengan privilege user)
3. Import struktur database dari tenant reference
4. Buat direktori storage: `storage/tenants/[RESCODE]/`
5. Buat direktori public: `public/tenants/[RESCODE]/`
6. Simpan data ke master database
7. Clear cache tenant

**Langkah Manual Setelah Tenant Dibuat:**
1. **Nginx Configuration**: Tambahkan virtual host
2. **SSL Certificate**: Generate dengan Let's Encrypt
3. **DNS Configuration**: Arahkan domain ke server
4. **Testing**: Akses domain dan verifikasi

### 3. Lihat Detail Tenant (Show)
**URL:** `/admin/tenants/{id}`

Menampilkan:
- Informasi umum (domain, app name, signature, rescode, email, status)
- Konfigurasi database (host, port, database, username)
- Features enabled
- Storage paths
- Environment variables (jika ada, password disembunyikan)
- Created & updated timestamps

### 4. Edit Tenant (Edit)
**URL:** `/admin/tenants/{id}/edit`

**Dapat Diubah:**
- Domain
- App Name
- Signature
- Email From
- Database Host, Port, Name, Username
- Password (opsional, kosongkan jika tidak ingin mengubah)
- Features (checkbox)
- Status (Active/Inactive)
- Catatan

**Tidak Dapat Diubah:**
- Rescode (field disabled)

### 5. Hapus Tenant (Delete)
**Aksi:** Button delete di tabel

**Proses:**
1. Konfirmasi JavaScript
2. Soft delete (data tidak benar-benar dihapus)
3. Tenant masih bisa di-restore dari database jika diperlukan

## Navigation Menu

Menu "Tenant Management" muncul di navbar kiri (hanya untuk user yang sudah login).

## Technical Details

### Routes
```php
Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::resource('tenants', 'TenantManagementController');
    Route::post('/tenants/{tenant}/toggle', 'TenantManagementController@toggleStatus')
         ->name('tenants.toggle');
});
```

### Controller
`app/Http/Controllers/TenantManagementController.php`

### Views
- `resources/views/tenants/index.blade.php` - Daftar tenant
- `resources/views/tenants/create.blade.php` - Form tambah
- `resources/views/tenants/edit.blade.php` - Form edit
- `resources/views/tenants/show.blade.php` - Detail tenant

### Model
`app/Tenant.php` dengan:
- Connection: `master` database
- Encryption: Password dan tokens
- Caching: 1 jam
- Soft Deletes: Data bisa di-restore

## Workflow Menambah Tenant via UI

### Persiapan
1. Login ke aplikasi
2. Klik menu "Tenant Management"
3. Klik button "Tambah Tenant Baru"

### Isi Form
```
Domain: newclient.alus.co.id
App Name: PT NEW CLIENT INTERNET
Signature: Hormat kami, Tim Support
Rescode: newclient
Email From: no-reply@newclient.alus.co.id

DB Host: localhost
DB Port: 3306
DB Name: isp_newclient
DB Username: newclient_user
DB Password: ********
☑ Auto Create Database

Features:
☑ Accounting
☑ Ticketing
☑ WhatsApp Integration
☐ Payment Gateway

Catatan: Client baru, trial 30 hari
```

### Submit
- Klik "Tambah Tenant"
- Sistem akan:
  - Membuat database `isp_newclient`
  - Membuat user database dengan privilege
  - Import struktur dari tenant reference
  - Membuat folder storage dan public
  - Simpan konfigurasi ke master DB

### Konfigurasi Manual
1. **Nginx Config** (`/etc/nginx/sites-available/newclient.alus.co.id`):
```nginx
server {
    listen 80;
    server_name newclient.alus.co.id;
    root /var/www/kencana.alus.co.id/public;
    
    # ... (copy dari config tenant lain)
}
```

2. **Enable Site:**
```bash
ln -s /etc/nginx/sites-available/newclient.alus.co.id /etc/nginx/sites-enabled/
nginx -t
systemctl reload nginx
```

3. **SSL Certificate:**
```bash
certbot --nginx -d newclient.alus.co.id
```

4. **DNS Configuration:**
- Arahkan `newclient.alus.co.id` ke IP server
- Tunggu propagasi DNS (5-15 menit)

5. **Testing:**
```bash
curl -I https://newclient.alus.co.id
```

## Troubleshooting

### Error: Database creation failed
**Penyebab:** User MySQL tidak punya privilege CREATE DATABASE

**Solusi:**
```bash
mysql -u root -p
GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION;
FLUSH PRIVILEGES;
```

### Error: Directory creation failed
**Penyebab:** Permission denied

**Solusi:**
```bash
chown -R www-data:www-data /var/www/kencana.alus.co.id/storage
chown -R www-data:www-data /var/www/kencana.alus.co.id/public
chmod -R 775 /var/www/kencana.alus.co.id/storage
```

### Error: Cannot find reference database
**Penyebab:** Tidak ada tenant reference untuk import struktur

**Solusi:**
1. Buat database manual dulu
2. Import SQL dump dari tenant existing
3. Atau kosongkan checkbox "Auto Create Database" dan buat manual

### Toggle Status Tidak Bekerja
**Penyebab:** JavaScript error atau CSRF token

**Solusi:**
1. Cek console browser (F12)
2. Pastikan jQuery loaded
3. Clear browser cache
4. Periksa CSRF token di meta tag

## Advanced: Environment Variables

Untuk menambah custom ENV variables per tenant, gunakan Tinker atau SQL:

### Via Tinker
```php
php artisan tinker

$tenant = App\Tenant::where('rescode', 'newclient')->first();
$tenant->env_variables = [
    'CUSTOM_API_KEY' => 'your-api-key',
    'CUSTOM_API_URL' => 'https://api.example.com',
    'MAX_UPLOAD_SIZE' => '10M'
];
$tenant->save();
```

### Via SQL
```sql
UPDATE tenants 
SET env_variables = '{"CUSTOM_API_KEY":"your-api-key","MAX_UPLOAD_SIZE":"10M"}'
WHERE rescode = 'newclient';
```

### Akses di Code
```php
$apiKey = tenant_env('CUSTOM_API_KEY');
$maxSize = tenant_env('MAX_UPLOAD_SIZE', '5M'); // with default
```

## Security Notes

1. **Password Encryption:** Database password di-encrypt dengan Laravel Crypt
2. **CSRF Protection:** Semua form dilindungi CSRF token
3. **Authentication:** Routes protected dengan middleware auth
4. **Soft Delete:** Data tidak benar-benar dihapus
5. **Input Validation:** Server-side validation pada controller

## Backup & Recovery

### Backup Tenant Config
```bash
php artisan tinker
App\Tenant::all()->toJson(JSON_PRETTY_PRINT)
```

### Export ke File
```bash
mysql -u root -p isp_master -e "SELECT * FROM tenants" > tenants_backup.sql
```

### Restore Tenant
```sql
INSERT INTO tenants (domain, app_name, rescode, ...)
VALUES ('domain.com', 'App Name', 'rescode', ...);
```

## Related Commands (CLI Alternative)

Untuk user yang prefer CLI:

```bash
# List tenants
php artisan tenant:list

# Create tenant via CLI
php artisan tenant:create

# Migrate from old config
php artisan tenant:migrate-from-config
```

## Support

Jika mengalami masalah:
1. Check logs: `storage/logs/laravel.log`
2. Check web server logs: `/var/log/nginx/error.log`
3. Gunakan browser console (F12) untuk debug JavaScript
4. Test dengan `php artisan tenant:list` untuk verifikasi data

## Changelog

### v1.0 (2025-01-16)
- Initial release
- Full CRUD functionality
- Auto database creation
- Directory creation
- Toggle status AJAX
- Environment variables support

---

**Last Updated:** 2025-01-16
**Author:** System Administrator
