# Quick Start: Environment Variables via Admin UI

## 🎯 Langkah Cepat

### 1. Login Admin Panel
```
https://kencana.alus.co.id/admin/login
Email: admin@kencana.alus.co.id
Password: Admin123!@#
```

### 2. Edit Tenant
- Klik menu **"Tenant Management"**
- Pilih tenant yang akan di-edit
- Klik tombol **"Edit"**

### 3. Tambah Environment Variables
- Scroll ke section **"Custom Environment Variables"**
- Klik tombol **"+ Tambah Variable"**
- Input KEY dan VALUE:
  ```
  KEY: WHATSAPP_TOKEN
  VALUE: EAAJf1234567890_your_token_here
  ```
- Klik **"+ Tambah Variable"** lagi untuk variable tambahan
- Klik **"Update Tenant"**

### 4. Contoh Variables Yang Sering Digunakan

#### WhatsApp Integration
```
WHATSAPP_TOKEN = EAAJf1234567890...
WHATSAPP_NUMBER = 628123456789
WHATSAPP_WEBHOOK_VERIFY = verify_token_123
```

#### Xendit Payment Gateway
```
XENDIT_SECRET = xnd_development_xxx atau xnd_production_xxx
XENDIT_PUBLIC = xnd_public_xxx
XENDIT_WEBHOOK_TOKEN = webhook_token_xxx
```

#### Custom SMTP
```
SMTP_HOST = smtp.gmail.com
SMTP_PORT = 587
SMTP_USERNAME = noreply@domain.com
SMTP_PASSWORD = app_password_here
SMTP_ENCRYPTION = tls
```

#### FTP Configuration
```
FTP_USER = backup_user
FTP_PASSWORD = SecureFtpPass!@#
FTP_HOST = ftp.backup-server.com
FTP_PORT = 21
```

#### Notification Delay (WA Blast)
```
# Tenant dengan gateway cepat (tanpa rate-limit ketat)
NOTIF_DELAY_MIN = 10
NOTIF_DELAY_MAX = 30
NOTIF_LONG_PAUSE_EVERY = 50
NOTIF_LONG_PAUSE_EXTRA = 60

# Tenant dengan gateway ketat (anti-spam)
NOTIF_DELAY_MIN = 180
NOTIF_DELAY_MAX = 360
NOTIF_LONG_PAUSE_EVERY = 20
NOTIF_LONG_PAUSE_EXTRA = 600
```

#### Queue Worker
```
QUEUE_SLEEP    = 3
QUEUE_TRIES    = 3
QUEUE_TIMEOUT  = 120
QUEUE_MAX_JOBS = 500
```
> ⚠️ Untuk `QUEUE_*`, sebaiknya gunakan panel **Settings** di halaman
> Queue Worker Monitor (`/admin/tenants/{id}`) agar supervisor conf ikut diperbarui.

#### Custom API Keys
```
GOOGLE_MAPS_API_KEY = AIzaSyXXXXXX
FIREBASE_API_KEY = firebase_key_xxx
CUSTOM_API_ENDPOINT = https://api.custom.com
```

## 🔍 Melihat Variables Yang Sudah Ada

### Via Admin UI
1. Klik **"Detail"** pada tenant
2. Scroll ke section **"Environment Variables"**
3. Semua variables terlihat di tabel

### Via Tinker (Advanced)
```bash
php artisan tinker
```
```php
$tenant = App\Tenant::where('rescode', 'KC')->first();
print_r($tenant->env_variables);
```

## ✅ Testing di Aplikasi

### Di Blade View
```blade
<!-- Test apakah variable terbaca -->
<p>WhatsApp Token: {{ tenant_env('WHATSAPP_TOKEN', 'Not Set') }}</p>
<p>Xendit Secret: {{ tenant_env('XENDIT_SECRET', 'Not Set') }}</p>
```

### Di Controller
```php
public function test()
{
    $waToken = tenant_env('WHATSAPP_TOKEN');
    $xenditSecret = tenant_env('XENDIT_SECRET');
    
    return response()->json([
        'whatsapp_token' => $waToken ? 'Set' : 'Not Set',
        'xendit_secret' => $xenditSecret ? 'Set' : 'Not Set',
    ]);
}
```

## 🎨 UI Features

✅ **Dynamic Add/Remove**: Tambah atau hapus variable dengan mudah
✅ **No Page Reload**: Tambah multiple variables sekaligus
✅ **Validation**: Empty keys automatically skipped
✅ **Auto Uppercase**: Keys automatically converted to UPPERCASE
✅ **Visual Feedback**: Info box dengan contoh variables

## 📝 Tips

1. **Naming Convention**: 
   - Gunakan UPPERCASE: `WHATSAPP_TOKEN` ✅
   - Gunakan underscore separator: `API_KEY_SECRET` ✅
   - Avoid spaces: `API KEY` ❌

2. **Priority Order**:
   ```
   Database JSON → Global .env → Default Value
   ```

3. **Clear Cache Jika Perlu**:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

4. **Security**: 
   - Tenant-specific keys → Database ✅
   - Master/root credentials → .env file ✅

## 🚨 Troubleshooting

### Variable tidak terbaca?
1. Check spelling KEY (case-sensitive!)
2. Clear cache: `php artisan config:clear`
3. Reload halaman tenant
4. Check via Tinker apakah tersimpan

### Tombol "Tambah Variable" tidak muncul?
1. Clear browser cache
2. Hard refresh: Ctrl+F5 (Windows) atau Cmd+Shift+R (Mac)
3. Check browser console untuk errors

### Update tidak tersimpan?
1. Check validasi form (red error messages)
2. Check database connection
3. Check logs: `storage/tenants/[RESCODE]/logs/laravel.log`

## 🎉 Done!

Sekarang setiap tenant bisa punya konfigurasi sendiri tanpa edit `.env` file!

**Admin UI**: https://kencana.alus.co.id/admin/login
**Documentation**: ENV_VARIABLES_DATABASE_GUIDE.md
