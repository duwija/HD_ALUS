# Environment Variables Database Management

## Overview
Sistem multi-tenant sekarang mendukung **custom environment variables per tenant** yang disimpan di database (kolom JSON `env_variables` di tabel `tenants`).

## Priority Hierarchy

```
1. Database JSON (tenant-specific)  ← HIGHEST PRIORITY
2. Global .env file                 ← FALLBACK
3. Default value                    ← LAST RESORT
```

## Cara Kerja

### 1. Via Admin UI (Recommended)

#### Saat Create Tenant:
1. Login ke admin panel: `https://domain.com/admin/login`
2. Klik **"Tambah Tenant Baru"**
3. Scroll ke section **"Custom Environment Variables"**
4. Klik **"Tambah Variable"**
5. Input:
   - **Key**: `WHATSAPP_TOKEN` (uppercase, no spaces)
   - **Value**: `EAAJf1234567890...`
6. Ulangi untuk variable lain
7. Simpan tenant

#### Saat Edit Tenant:
1. Klik **"Edit"** pada tenant yang diinginkan
2. Scroll ke section **"Custom Environment Variables"**
3. Tambah/edit/hapus variables sesuai kebutuhan
4. Klik **"Update Tenant"**

### 2. Via Tinker (Advanced)

```bash
php artisan tinker
```

```php
use App\Tenant;

// Get tenant
$tenant = Tenant::where('rescode', 'KC')->first();

// Set env variables
$tenant->env_variables = [
    'WHATSAPP_TOKEN' => 'EAAJf1234567890...',
    'WHATSAPP_NUMBER' => '628123456789',
    'XENDIT_SECRET' => 'xnd_development_...',
    'XENDIT_PUBLIC' => 'xnd_public_...',
    'SMTP_USERNAME' => 'noreply@tenant.com',
    'SMTP_PASSWORD' => 'password123',
    'SMTP_HOST' => 'smtp.gmail.com',
    'SMTP_PORT' => '587',
];

$tenant->save();
```

### 3. Via Direct SQL (Expert Only)

```sql
UPDATE tenants 
SET env_variables = JSON_OBJECT(
    'WHATSAPP_TOKEN', 'EAAJf1234567890...',
    'XENDIT_SECRET', 'xnd_development_...'
)
WHERE rescode = 'KC';
```

## Menggunakan di Kode

### Blade Template

```blade
{{-- Ambil dari database tenant dulu, fallback ke .env --}}
<p>WhatsApp: {{ tenant_env('WHATSAPP_NUMBER', '628xxx') }}</p>
<p>SMTP: {{ tenant_env('SMTP_USERNAME', 'default@mail.com') }}</p>
```

### Controller/Model

```php
use App\Helpers\TenantHelpers;

// Method 1: Via helper
$waToken = tenant_env('WHATSAPP_TOKEN');
$xenditSecret = tenant_env('XENDIT_SECRET', 'default_key');

// Method 2: Direct dari tenant object
$tenant = tenant();
$waNumber = $tenant['env_variables']['WHATSAPP_NUMBER'] ?? '628xxx';
```

### Config Files

Jika ingin override config Laravel:

```php
// config/services.php
return [
    'xendit' => [
        'secret' => tenant_env('XENDIT_SECRET', env('XENDIT_SECRET')),
        'public' => tenant_env('XENDIT_PUBLIC', env('XENDIT_PUBLIC')),
    ],
    
    'whatsapp' => [
        'token' => tenant_env('WHATSAPP_TOKEN', env('WHATSAPP_TOKEN')),
        'number' => tenant_env('WHATSAPP_NUMBER', env('WHATSAPP_NUMBER')),
    ],
];
```

## Contoh Use Cases

### 1. WhatsApp Integration Per Tenant

```php
// Setiap tenant punya WhatsApp token sendiri
$tenant = Tenant::where('rescode', 'KC')->first();
$tenant->env_variables = [
    'WHATSAPP_TOKEN' => 'EAAJf_KC_token',
    'WHATSAPP_NUMBER' => '628123456789',
];
$tenant->save();

// Di controller
$waToken = tenant_env('WHATSAPP_TOKEN');
$waNumber = tenant_env('WHATSAPP_NUMBER');
// Kirim pesan WhatsApp menggunakan token tenant
```

### 2. Payment Gateway Per Tenant

```php
// Tenant A pakai Xendit production
$tenantA->env_variables = [
    'XENDIT_SECRET' => 'xnd_production_secret',
    'XENDIT_PUBLIC' => 'xnd_production_public',
];

// Tenant B pakai Xendit development
$tenantB->env_variables = [
    'XENDIT_SECRET' => 'xnd_development_secret',
    'XENDIT_PUBLIC' => 'xnd_development_public',
];

// Di PaymentController
$xenditSecret = tenant_env('XENDIT_SECRET');
// Otomatis dapat key sesuai tenant
```

### 3. Custom SMTP Per Tenant

```php
$tenant->env_variables = [
    'SMTP_HOST' => 'smtp.gmail.com',
    'SMTP_PORT' => '587',
    'SMTP_USERNAME' => 'noreply@tenantdomain.com',
    'SMTP_PASSWORD' => 'app_password_xxx',
    'SMTP_ENCRYPTION' => 'tls',
    'MAIL_FROM_ADDRESS' => 'noreply@tenantdomain.com',
    'MAIL_FROM_NAME' => 'Tenant Name',
];

// Di mail config atau saat kirim email
config([
    'mail.mailers.smtp.host' => tenant_env('SMTP_HOST'),
    'mail.mailers.smtp.port' => tenant_env('SMTP_PORT'),
    'mail.mailers.smtp.username' => tenant_env('SMTP_USERNAME'),
    'mail.mailers.smtp.password' => tenant_env('SMTP_PASSWORD'),
]);
```

### 4. FTP Configuration Per Tenant

```php
// Tenant A - Production FTP
$tenantA->env_variables = [
    'FTP_USER' => 'backup_prod',
    'FTP_PASSWORD' => 'SecureFtpPass!@#2024',
    'FTP_HOST' => 'ftp.backup-server.com',
    'FTP_PORT' => '21',
];

// Tenant B - Development FTP
$tenantB->env_variables = [
    'FTP_USER' => 'backup_dev',
    'FTP_PASSWORD' => 'DevFtpPass123',
    'FTP_HOST' => 'ftp.dev-server.com',
    'FTP_PORT' => '21',
];

// Di BackupController atau FTP service
$ftpUser = tenant_env('FTP_USER');
$ftpPassword = tenant_env('FTP_PASSWORD');
$ftpHost = tenant_env('FTP_HOST', 'localhost');
$ftpPort = tenant_env('FTP_PORT', '21');

// Connect to FTP
$conn = ftp_connect($ftpHost, $ftpPort);
ftp_login($conn, $ftpUser, $ftpPassword);
// Upload backup files...
```

**Use cases FTP:**
- Automated database backup to remote FTP server
- File synchronization untuk disaster recovery
- Remote backup storage isolation per tenant
- Integration dengan external backup services

## Best Practices

### ✅ DO's

1. **Gunakan UPPERCASE untuk key**
   ```php
   'WHATSAPP_TOKEN' => 'xxx'  // ✅ Good
   'whatsapp_token' => 'xxx'  // ❌ Bad
   ```

2. **Gunakan naming convention yang jelas**
   ```php
   'XENDIT_SECRET_KEY' => 'xxx'  // ✅ Clear
   'XDT_S' => 'xxx'              // ❌ Cryptic
   ```

3. **Fallback ke global .env untuk shared configs**
   ```php
   // Database credentials tetap di .env global
   DB_HOST=127.0.0.1
   
   // API keys bisa per tenant di database
   WHATSAPP_TOKEN=(per tenant)
   ```

4. **Always provide default values**
   ```php
   tenant_env('CUSTOM_VAR', 'safe_default')  // ✅ Good
   tenant_env('CUSTOM_VAR')                  // ⚠️ Could be null
   ```

### ❌ DON'Ts

1. **Jangan simpan credentials super sensitive di JSON**
   - Master database password → .env file
   - Root API keys → .env file
   - Tenant-specific keys → database OK

2. **Jangan hardcode values**
   ```php
   $token = 'EAAJf1234567890';  // ❌ Bad
   $token = tenant_env('WHATSAPP_TOKEN');  // ✅ Good
   ```

3. **Jangan lupa clear cache setelah update**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

## Viewing Current Values

### Via Admin UI
1. Login admin panel
2. Klik **"Detail"** pada tenant
3. Scroll ke **"Environment Variables"**
4. Semua variables terlihat di sana

### Via Tinker
```php
$tenant = Tenant::where('rescode', 'KC')->first();
print_r($tenant->env_variables);
```

### Via SQL
```sql
SELECT rescode, env_variables 
FROM tenants 
WHERE rescode = 'KC';
```

## Troubleshooting

### Variable tidak terbaca?

1. **Check apakah tenant ter-load:**
   ```php
   dd(app('tenant'));  // Should return tenant array
   ```

2. **Check key spelling:**
   ```php
   // Case sensitive!
   tenant_env('whatsapp_token')  // ❌ Won't work
   tenant_env('WHATSAPP_TOKEN')  // ✅ Works
   ```

3. **Clear cache:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

4. **Check database:**
   ```sql
   SELECT env_variables FROM tenants WHERE rescode = 'KC';
   ```

## Database Schema

```sql
CREATE TABLE `tenants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  ...
  `env_variables` json DEFAULT NULL,  -- ← Custom ENV per tenant
  ...
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Helper Function Source

File: `app/Helpers/TenantHelpers.php`

```php
function tenant_env($key, $default = null)
{
    // Try from tenant's custom env_variables (JSON) first
    $tenant = app()->bound('tenant') ? app('tenant') : null;
    
    if ($tenant && isset($tenant['env_variables'][$key])) {
        return $tenant['env_variables'][$key];
    }
    
    // Try from tenant config
    $value = config('tenant.' . strtolower($key));
    
    if ($value !== null) {
        return $value;
    }
    
    // Fallback to global .env file
    return env(strtoupper($key), $default);
}
```

## Security Notes

⚠️ **IMPORTANT:**
- ENV variables tersimpan **plain text** di database JSON
- Jangan simpan super sensitive data (master passwords, root keys)
- Tenant-specific API keys/tokens **OK**
- Pertimbangkan encryption untuk production jika perlu

## Migration dari .env ke Database

Jika punya tenant dengan ENV di `.env` file:

```bash
# 1. Backup dulu
cp .env .env.backup

# 2. Via tinker
php artisan tinker
```

```php
$tenant = Tenant::where('rescode', 'KC')->first();
$tenant->env_variables = [
    'WHATSAPP_TOKEN' => env('KC_WHATSAPP_TOKEN'),
    'XENDIT_SECRET' => env('KC_XENDIT_SECRET'),
    // ... copy dari .env
];
$tenant->save();

// 3. Remove dari .env file setelah verify works
```

## Summary

- ✅ Custom ENV variables per tenant via database JSON
- ✅ Priority: Database → .env → Default
- ✅ Managed via Admin UI (user-friendly)
- ✅ Support WhatsApp, Payment, SMTP, Custom APIs
- ✅ `tenant_env()` helper untuk akses mudah
- ✅ Tested dan working!

**Happy Multi-Tenanting! 🚀**
