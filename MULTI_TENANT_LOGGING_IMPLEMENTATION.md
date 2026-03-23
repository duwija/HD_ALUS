# Multi-Tenant Logging Implementation

## Overview
Implementasi sistem logging terpisah per tenant untuk aplikasi multi-tenant Kencana Alus ISP.

## Tanggal Implementasi
5 Februari 2026

## Komponen yang Dibuat/Dimodifikasi

### 1. TenantLogger Class
**File:** `app/Logging/TenantLogger.php`

Custom Monolog logger yang membuat log path dinamis berdasarkan tenant ID:
- Log disimpan di `storage/logs/tenant_{tenant_id}/laravel.log`
- Otomatis membuat direktori jika belum ada
- Mendapatkan tenant ID dari: session → config → env → auth user → default

### 2. SetTenantContext Middleware
**File:** `app/Http/Middleware/SetTenantContext.php`

Middleware global yang:
- Resolve tenant ID dari berbagai sumber (user, session, env, subdomain)
- Menyimpan tenant ID ke session dan config
- Menambahkan tenant context ke log

**Registered di:** `app/Http/Kernel.php` → `$middleware` array

### 3. FilterTenantLogs Middleware
**File:** `app/Http/Middleware/FilterTenantLogs.php`

Middleware untuk log-viewer yang:
- Filter log files berdasarkan tenant yang sedang login
- Set runtime configuration untuk log-viewer
- Hanya aktif pada route `log-viewer*`

**Registered di:** 
- `app/Http/Kernel.php` → `$routeMiddleware` dengan key `filter.tenant.logs`
- `config/log-viewer.php` → `middleware` array

### 4. Logging Configuration
**File:** `config/logging.php`

Perubahan:
- Default stack channel sekarang menggunakan `tenant` channel
- Tambah custom channel `tenant` yang menggunakan `TenantLogger` class

```php
'stack' => [
    'driver' => 'stack',
    'channels' => ['tenant'],
],

'tenant' => [
    'driver' => 'custom',
    'via' => App\Logging\TenantLogger::class,
],
```

### 5. Log Viewer Configuration
**File:** `config/log-viewer.php`

Perubahan:
- Tambah `FilterTenantLogs` middleware
- Include files pattern: `tenant_*/**.log` dan `*.log`

### 6. LogViewerServiceProvider
**File:** `app/Providers/LogViewerServiceProvider.php`

Service provider placeholder (untuk future customization).

**Registered di:** `config/app.php` → `providers` array

## Cara Kerja

### Flow Logging:
1. Request masuk → `SetTenantContext` middleware resolve tenant ID
2. Tenant ID disimpan di session dan config
3. Aplikasi menulis log → Laravel menggunakan `tenant` channel
4. `TenantLogger` membuat path `storage/logs/tenant_{id}/laravel.log`
5. Log ditulis ke file tenant-specific

### Flow Log Viewer:
1. User akses `/user/log` atau `/log-viewer`
2. `FilterTenantLogs` middleware intercept request
3. Middleware set config untuk hanya include log tenant yang aktif
4. Log viewer hanya menampilkan log dari `storage/logs/tenant_{id}/`

## Struktur Direktori Log

```
storage/logs/
├── tenant_default/
│   └── laravel.log
├── tenant_1/
│   └── laravel.log
├── tenant_2/
│   └── laravel.log
└── tenant_xxx/
    └── laravel.log
```

## Keuntungan

1. **Isolasi Data**: Setiap tenant memiliki log file terpisah
2. **Privacy**: Tenant tidak bisa melihat log tenant lain
3. **Debugging**: Lebih mudah debug issue spesifik tenant
4. **Audit Trail**: Log per tenant untuk compliance
5. **Performance**: Log viewer lebih cepat karena file lebih kecil

## Testing

Untuk test implementasi:

```bash
# Test write log
\Log::info('Test multi-tenant logging');

# Cek file log dibuat
ls -la storage/logs/tenant_*/

# Akses log viewer
# https://kencana.alus.co.id/user/log
```

## Catatan Penting

1. **Tenant ID Source Priority:**
   - Auth user tenant_id
   - Session tenant_id
   - Environment variable TENANT_ID
   - Subdomain (tenant1.kencana.alus.co.id)
   - Default: 'default'

2. **Backward Compatibility:**
   - Log existing mungkin masih ada di `storage/logs/laravel.log`
   - Setelah implementasi, log baru akan ke folder tenant

3. **Permissions:**
   - Pastikan web server punya write access ke `storage/logs/tenant_*/`
   - Directory dibuat otomatis dengan permission 0755

## Next Steps (Optional)

1. Tambah rotation policy per tenant
2. Implementasi log aggregation untuk admin
3. Export log per tenant
4. Dashboard analytics per tenant
5. Log retention policy per tenant

## Rollback Plan

Jika perlu rollback ke single log file:

1. Edit `config/logging.php`:
   ```php
   'stack' => [
       'channels' => ['single'],
   ],
   ```

2. Remove middleware dari `app/Http/Kernel.php`
3. Clear config: `php artisan config:clear`
