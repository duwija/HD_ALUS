# Panduan ENV Variables per Tenant

Sistem multi-tenant sekarang mendukung **ENV variables dinamis per tenant** yang disimpan di database.

---

## Cara Kerja

Setiap tenant bisa memiliki konfigurasi ENV yang berbeda-beda, disimpan di kolom `env_variables` (JSON) di tabel `tenants`.

### ENV Variables yang Didukung:

> **Quick Reference** — semua key yang tersedia di `env_variables`:

| # | Key | Kategori | Wajib? |
|---|-----|----------|--------|
| 1 | `mail_mailer` | Mail | Tidak |
| 2 | `mail_host` | Mail | Ya |
| 3 | `mail_port` | Mail | Ya |
| 4 | `mail_username` | Mail | Ya |
| 5 | `mail_password` | Mail | Ya |
| 6 | `mail_encryption` | Mail | Ya |
| 7 | `mail_from_address` | Mail | Tidak |
| 8 | `mail_from_name` | Mail | Tidak |
| 9 | `whatsapp_url` | WA Business API | Tidak |
| 10 | `whatsapp_token` | WA Business API | Tidak |
| 11 | `wa_gateway_url` | WA Gateway | Tidak |
| 12 | `wa_group_payment` | WA Gateway | Tidak |
| 13 | `wa_group_support` | WA Gateway | Tidak |
| 14 | `wa_group_vendor` | WA Gateway | Tidak |
| 15 | `wa_session_name` | WA Gateway | Tidak |
| 16 | `payment_wa` | WA Gateway | Tidak |
| 17 | `xendit_key` | Payment Xendit | Tidak |
| 18 | `xendit_callback_token` | Payment Xendit | Tidak |
| 19 | `xendit_public` | Payment Xendit | Tidak |
| 20 | `xendit_secret` | Payment Xendit | Tidak |
| 21 | `tripay_endpoint` | Payment Tripay | Tidak |
| 22 | `tripay_apikey` | Payment Tripay | Tidak |
| 23 | `tripay_privatekey` | Payment Tripay | Tidak |
| 24 | `tripay_merchantcode` | Payment Tripay | Tidak |
| 25 | `google_maps_api_key` | Maps | Tidak |
| 26 | `coordinate_center` | Maps | Tidak |
| 27 | `coordinate_zoom` | Maps | Tidak |
| 28 | `pppoe_password` | Network | Tidak |
| 29 | `router_host` | Network | Tidak |
| 30 | `router_username` | Network | Tidak |
| 31 | `router_password` | Network | Tidak |
| 32 | `probe_key` | Network | Tidak |
| 33 | `probe_domain` | Network | Tidak |
| 34 | `sms_gateway_url` | SMS | Tidak |
| 35 | `sms_gateway_key` | SMS | Tidak |
| 36 | `telegram_bot_token` | Telegram | Tidak |
| 37 | `telegram_chat_id` | Telegram | Tidak |
| 38 | `telegram_group_payment` | Telegram | Tidak |
| 39 | `ftp_user` | FTP | Tidak |
| 40 | `ftp_password` | FTP | Tidak |
| 41 | `company_name` | Perusahaan | Tidak |
| 42 | `company` | Perusahaan | Tidak |
| 43 | `company_address1` | Perusahaan | Tidak |
| 44 | `company_address2` | Perusahaan | Tidak |
| 45 | `inv_note` | Perusahaan | Tidak |
| 46 | `domain_name` | Perusahaan | Tidak |
| 47 | `signature` | Perusahaan | Tidak |
| 48 | `backup_path` | System | Tidak |
| 49 | `phyton_dir` | System | Tidak |
| 50 | `report_email` | System | Tidak |
| 51 | `whatsapp_noc` | Perusahaan | Tidak |
| 52 | `marketing_email` | Mail | Tidak |

---

#### 1. **Mail Configuration**
```json
{
  "mail_mailer": "smtp",
  "mail_host": "mail.example.com",
  "mail_port": "465",
  "mail_username": "noreply@example.com",
  "mail_password": "mailpassword",
  "mail_encryption": "ssl",
  "mail_from_address": "noreply@example.com",
  "mail_from_name": "Nama ISP Anda",
  "marketing_email": "duwija@trikamedia.com"
}
```
**Kegunaan:** Konfigurasi SMTP untuk pengiriman email notifikasi, invoice, reset password per tenant.

| Key | Wajib | Keterangan |
|-----|-------|------------|
| `mail_mailer` | Tidak | Driver mailer, default `smtp` |
| `mail_host` | Ya | Hostname SMTP server |
| `mail_port` | Ya | Port SMTP (465=SSL, 587=TLS, 25=plain) |
| `mail_username` | Ya | Username/email akun SMTP |
| `mail_password` | Ya | Password akun SMTP |
| `mail_encryption` | Ya | `ssl` atau `tls` |
| `mail_from_address` | Tidak | Alamat pengirim, fallback ke `mail_from` kolom |
| `mail_from_name` | Tidak | Nama pengirim, fallback ke nama aplikasi |
| `marketing_email` | Tidak | Email tujuan notifikasi order add-on dari portal pelanggan |

#### 2. **WhatsApp Business API**
```json
{
  "whatsapp_url": "http://localhost:3000",
  "whatsapp_token": "your-token"
}
```
**Kegunaan:** Integrasi WhatsApp Business API (Cloud API) untuk notifikasi reminder pembayaran, support tiket.

#### 3. **WA Gateway (Self-hosted)**
```json
{
  "wa_gateway_url": "http://127.0.0.1:3007",
  "wa_group_payment": "628xxx@g.us",
  "wa_group_support": "628yyy@g.us",
  "wa_group_vendor": "628zzz@g.us",
  "wa_session_name": "tenant-session",
  "payment_wa": "62812345678"
}
```
**Kegunaan:** Self-hosted WA Gateway (Baileys/WA-Web) untuk notifikasi payment, konfirmasi tiket, dan broadcast ke grup.

| Key | Keterangan |
|-----|------------|
| `wa_gateway_url` | URL endpoint WA gateway lokal |
| `wa_group_payment` | Group ID untuk notifikasi pembayaran |
| `wa_group_support` | Group ID untuk notifikasi tiket support |
| `wa_group_vendor` | Group ID untuk notifikasi vendor/teknisi |
| `wa_session_name` | Nama sesi WhatsApp (untuk multi-session) |
| `payment_wa` | Nomor WA CS untuk info pembayaran di invoice |
| `whatsapp_noc` | Nomor WA NOC/Support untuk tombol "Buat Laporan" di portal pelanggan app |

#### 4. **Payment Gateway — Xendit**
```json
{
  "xendit_key": "xnd_development_xxx",
  "xendit_callback_token": "callback-token",
  "xendit_public": "xnd_public_xxx",
  "xendit_secret": "xnd_development_secret_xxx"
}
```
**Kegunaan:** Payment gateway Xendit untuk pembayaran invoice pelanggan (VA, QRIS, e-wallet).

#### 5. **Payment Gateway — Tripay**
```json
{
  "tripay_endpoint": "https://tripay.co.id/api/",
  "tripay_apikey": "DEV-xxxxxxxxxxxx",
  "tripay_privatekey": "private-key-xxx",
  "tripay_merchantcode": "T12345"
}
```
**Kegunaan:** Payment gateway Tripay untuk pembayaran invoice via transfer bank, minimarket, QRIS.

| Key | Keterangan |
|-----|------------|
| `tripay_endpoint` | `https://tripay.co.id/api/` (production) atau `https://tripay.co.id/api-sandbox/` |
| `tripay_apikey` | API key dari dashboard Tripay |
| `tripay_privatekey` | Private key untuk generate signature |
| `tripay_merchantcode` | Kode merchant Tripay |

#### 6. **Google Maps & Koordinat**
```json
{
  "google_maps_api_key": "AIzaSyCxxx",
  "coordinate_center": "-8.559888, 115.105733",
  "coordinate_zoom": "13"
}
```
**Kegunaan:** Integrasi Google Maps untuk tracking lokasi customer, visualisasi coverage area.

| Key | Keterangan |
|-----|------------|
| `google_maps_api_key` | API key Google Maps Platform |
| `coordinate_center` | Koordinat pusat peta (`lat, lng`), default saat pertama buka map |
| `coordinate_zoom` | Zoom level awal peta (1–20, default `13`) |

#### 7. **Router/Network**
```json
{
  "pppoe_password": "default-password",
  "router_host": "192.168.1.1",
  "router_username": "admin",
  "router_password": "admin123",
  "probe_key": "unique-probe-key-123",
  "probe_domain": "tenant.example.com"
}
```
**Kegunaan:** Koneksi ke router Mikrotik/Cisco untuk monitoring, provisioning PPPoE, dan manajemen bandwidth.

| Key | Keterangan |
|-----|------------|
| `pppoe_password` | Default password PPPoE untuk pelanggan baru |
| `router_host` | IP/hostname router utama |
| `router_username` | Username login router (API Mikrotik) |
| `router_password` | Password login router |
| `probe_key` | Secret key untuk endpoint probe monitoring |
| `probe_domain` | Domain tenant untuk identifikasi probe |

#### 8. **SMS Gateway**
```json
{
  "sms_gateway_url": "https://api.sms.com",
  "sms_gateway_key": "api-key"
}
```
**Kegunaan:** SMS notifikasi untuk reminder pembayaran, alert gangguan, atau verifikasi OTP.

#### 9. **Telegram**
```json
{
  "telegram_bot_token": "123456789:AAFxxx",
  "telegram_chat_id": "-1001234567890",
  "telegram_group_payment": "-1009876543210"
}
```
**Kegunaan:** Notifikasi real-time ke Telegram group untuk monitoring ticket, alert sistem, atau laporan.

| Key | Keterangan |
|-----|------------|
| `telegram_bot_token` | Token bot dari @BotFather |
| `telegram_chat_id` | Chat ID / group ID utama (notifikasi umum) |
| `telegram_group_payment` | Chat ID khusus untuk notifikasi pembayaran |

#### 10. **FTP Configuration**
```json
{
  "ftp_user": "ftpuser",
  "ftp_password": "ftppass123"
}
```
**Kegunaan:** Automated backup database ke remote FTP server, file synchronization, disaster recovery storage per tenant.

#### 11. **Informasi Perusahaan**
```json
{
  "company_name": "PT Kencana Network",
  "company": "kencana",
  "company_address1": "Jl. Raya Denpasar No. 1",
  "company_address2": "Bali, Indonesia 80361",
  "inv_note": "Terima kasih atas pembayaran Anda tepat waktu.",
  "domain_name": "kencana.co.id",
  "signature": "Tim Kencana Network"
}
```
**Kegunaan:** Data perusahaan yang tampil di invoice, laporan, email, dan header aplikasi.

| Key | Keterangan |
|-----|------------|
| `company_name` | Nama lengkap perusahaan |
| `company` | Nama singkat / slug perusahaan |
| `company_address1` | Alamat baris 1 |
| `company_address2` | Alamat baris 2 (kota, kodepos) |
| `inv_note` | Catatan tambahan di bagian bawah invoice |
| `domain_name` | Domain utama perusahaan (untuk link di email) |
| `signature` | Tanda tangan / nama pengirim di email |

#### 12. **System Paths**
```json
{
  "backup_path": "/home/backup/kencana",
  "phyton_dir": "/home/kencana/scripts",
  "report_email": "report@tenant.com"
}
```
**Kegunaan:** Path direktori custom untuk script otomasi, backup, dan email laporan.

| Key | Keterangan |
|-----|------------|
| `backup_path` | Path direktori backup database lokal |
| `phyton_dir` | Path direktori script Python (SNMP, OLT, dsb.) |
| `report_email` | Email tujuan pengiriman laporan otomatis |

#### 13. **Notification Delay**
```json
{
  "NOTIF_DELAY_MIN": 30,
  "NOTIF_DELAY_MAX": 90,
  "NOTIF_LONG_PAUSE_EVERY": 10,
  "NOTIF_LONG_PAUSE_EXTRA": 120
}
```
**Kegunaan:** Mengatur kecepatan kirim notifikasi WhatsApp massal (invoice reminder, blocked reminder) agar tidak terkena rate-limit gateway. Nilai ini dikonsumsi oleh `messageDelay()` di `SuminvoiceController`.

| Key | Default | Keterangan |
|-----|---------|------------|
| `NOTIF_DELAY_MIN` | `180` | Delay minimum antar pesan (detik). Default = 3 menit |
| `NOTIF_DELAY_MAX` | `360` | Delay maximum antar pesan (detik). Default = 6 menit |
| `NOTIF_LONG_PAUSE_EVERY` | `~20` (random) | Long pause setiap N pesan |
| `NOTIF_LONG_PAUSE_EXTRA` | `600` | Extra delay saat long pause (detik). Default = 10 menit |

> **Tip:** Untuk tenant dengan gateway cepat (tidak ada anti-spam), set ke nilai kecil:
> `NOTIF_DELAY_MIN=10`, `NOTIF_DELAY_MAX=30`, `NOTIF_LONG_PAUSE_EVERY=50`, `NOTIF_LONG_PAUSE_EXTRA=60`

#### 14. **Isolir Job Delay**
```json
{
  "ISOLIR_DELAY_MIN": 30,
  "ISOLIR_DELAY_MAX": 60,
  "ISOLIR_LONG_PAUSE_EVERY": 10,
  "ISOLIR_LONG_PAUSE_EXTRA": 120
}
```
**Kegunaan:** Mengatur kecepatan eksekusi job isolir (pemblokiran pelanggan) massal agar tidak membebani router/OLT sekaligus. Nilai ini dikonsumsi oleh `isolirDelay()` di `SendsCustomerNotification` trait.

| Key | Default | Keterangan |
|-----|---------|------------|
| `ISOLIR_DELAY_MIN` | `30` | Delay minimum antar job isolir (detik) |
| `ISOLIR_DELAY_MAX` | `60` | Delay maksimum antar job isolir (detik) |
| `ISOLIR_LONG_PAUSE_EVERY` | `10` | Long pause setiap N customer diproses |
| `ISOLIR_LONG_PAUSE_EXTRA` | `120` | Extra delay saat long pause (detik) |

> **Tip:** Jika router/OLT lambat merespons, naikkan nilai ini agar tidak ada job yang timeout:
> `ISOLIR_DELAY_MIN=60`, `ISOLIR_DELAY_MAX=120`, `ISOLIR_LONG_PAUSE_EVERY=5`, `ISOLIR_LONG_PAUSE_EXTRA=300`

> **Catatan:** Delay bersifat **kumulatif** — customer ke-N akan diproses setelah `delay × N` detik dari waktu dispatch, sehingga router tidak menerima request serentak.

#### 15. **Queue Worker Configuration**
```json
{
  "QUEUE_SLEEP": 3,
  "QUEUE_TRIES": 3,
  "QUEUE_TIMEOUT": 120,
  "QUEUE_MAX_JOBS": 500
}
```
**Kegunaan:** Mengatur parameter `artisan queue:work` untuk worker Supervisor milik tenant ini. Perubahan disimpan ke `env_variables` dan langsung diterapkan ke file `/etc/supervisord.d/{slug}.conf` saat di-save melalui Queue Worker Monitor di admin panel.

| Key | Default | Keterangan |
|-----|---------|------------|
| `QUEUE_SLEEP` | `3` | Jeda (detik) saat queue kosong |
| `QUEUE_TRIES` | `3` | Maksimum retry sebelum job masuk failed_jobs |
| `QUEUE_TIMEOUT` | `120` | Timeout maksimum per job (detik) |
| `QUEUE_MAX_JOBS` | `500` | Worker restart otomatis setelah memproses N job |

> **Catatan:** Perubahan `QUEUE_*` sebaiknya dilakukan via tombol **Settings** di Queue Worker Monitor (`/admin/tenants/{id}`) agar supervisor conf ikut diperbarui dan worker direstart otomatis.

---

## Cara Menggunakan di Code

### 1. **Helper Function: `tenant_env()`**

```php
// Get tenant-specific env variable
$password = tenant_env('pppoe_password', 'default');
$apiKey = tenant_env('google_maps_api_key');
$routerHost = tenant_env('router_host', '192.168.1.1');
```

### 2. **Config Helper**

```php
// Akan otomatis load dari tenant config
$apiKey = config('tenant.google_maps_api_key');
$password = config('tenant.pppoe_password');
```

### 3. **Standard ENV (with Fallback)**

```php
// Akan cek tenant config dulu, baru fallback ke .env
$key = env('GOOGLE_MAPS_API_KEY');  // otomatis dari tenant
```

---

## Cara Update ENV per Tenant

### Method 1: Via SQL

```sql
UPDATE tenants 
SET env_variables = JSON_OBJECT(
    'pppoe_password', 'tenant123',
    'google_maps_api_key', 'AIzaSyCxxx',
    'router_host', '10.0.0.1',
    'mail_host', 'smtp.tenant.com',
    'mail_username', 'noreply@tenant.com',
    'mail_password', 'mailpass123'
)
WHERE domain = 'tenant.example.com';
```

### Method 2: Via Tinker

```php
php artisan tinker

$tenant = \App\Tenant::where('domain', 'tenant.example.com')->first();
$tenant->env_variables = [
    'pppoe_password' => 'tenant123',
    'google_maps_api_key' => 'AIzaSyCxxx',
    'router_host' => '10.0.0.1',
    'mail_host' => 'smtp.tenant.com',
];
$tenant->save();
```

### Method 3: Via Update Array (Merge)

```php
$tenant = \App\Tenant::find(1);

// Get existing
$env = $tenant->env_variables ?? [];

// Merge new values
$env['new_key'] = 'new_value';
$env['another_key'] = 'another_value';

$tenant->env_variables = $env;
$tenant->save();

// Clear cache
\Cache::forget('tenant:' . $tenant->domain);
```

---

## Contoh Lengkap per Tenant

### Tenant A (Production ISP)
```json
{
  "mail_host": "mail.ispa.co.id",
  "mail_port": "465",
  "mail_username": "noreply@ispa.co.id",
  "mail_password": "EmailPass123",
  "mail_encryption": "ssl",
  "mail_from_address": "noreply@ispa.co.id",
  "mail_from_name": "ISP-A Network",
  "pppoe_password": "isp123!@#",
  "router_host": "192.168.1.1",
  "router_username": "admin",
  "router_password": "routerpass",
  "probe_key": "ispa-probe-key",
  "probe_domain": "ispa.co.id",
  "google_maps_api_key": "AIzaSyCProductionKey",
  "coordinate_center": "-8.559888, 115.105733",
  "coordinate_zoom": "13",
  "wa_gateway_url": "http://127.0.0.1:3007",
  "wa_group_payment": "-1001234567890",
  "wa_group_support": "-1009876543210",
  "payment_wa": "62812345678",
  "xendit_key": "xnd_production_xxx",
  "xendit_callback_token": "callback-token",
  "tripay_endpoint": "https://tripay.co.id/api/",
  "tripay_apikey": "DEV-xxxxxxxxxxxx",
  "tripay_privatekey": "privatekey-xxx",
  "tripay_merchantcode": "T12345",
  "telegram_bot_token": "123456789:AAFxxx",
  "telegram_chat_id": "-1001234567890",
  "telegram_group_payment": "-1009876543210",
  "sms_gateway_url": "https://sms.gateway.com/api",
  "sms_gateway_key": "sms-api-key-123",
  "ftp_user": "backup_ftp",
  "ftp_password": "FtpSecure123",
  "backup_path": "/home/backup/ispa",
  "phyton_dir": "/home/ispa/scripts",
  "company_name": "PT ISP-A Network",
  "company_address1": "Jl. Raya Denpasar No. 1",
  "company_address2": "Bali, Indonesia 80361",
  "inv_note": "Pembayaran maksimal tanggal 20 setiap bulan.",
  "signature": "Tim ISP-A Network",
  "domain_name": "ispa.co.id"
}
```

### Tenant B (Development / Minimal)
```json
{
  "mail_host": "smtp.mailtrap.io",
  "mail_port": "2525",
  "mail_username": "devmail",
  "mail_password": "devpass",
  "mail_encryption": "tls",
  "pppoe_password": "dev123",
  "router_host": "192.168.10.1",
  "google_maps_api_key": "AIzaSyCDevKey",
  "wa_gateway_url": "http://127.0.0.1:3007",
  "inv_note": "Ini tenant development."
}
```

---

## Priority Order

Sistem akan mencari ENV variable dengan urutan:

1. **Tenant `env_variables` (Database)** ← Tertinggi
2. **Tenant config dari Model**
3. **File `.env`** ← Fallback
4. **Default value**

Contoh:
```php
// 1. Cek tenant env_variables
// 2. Cek tenant->google_maps_api_key  
// 3. Cek .env GOOGLE_MAPS_API_KEY
// 4. Return 'default-key'
$key = tenant_env('google_maps_api_key', 'default-key');
```

---

## Best Practices

### 1. **Sensitive Data**
Gunakan encryption untuk data sensitif:
```php
// Password otomatis terencrypt di model
$tenant->db_password = 'plaintext'; // Saved as encrypted
```

### 2. **Caching**
Clear cache setelah update:
```php
\Cache::forget('tenant:' . $domain);
php artisan cache:clear
```

### 3. **Validation**
Validasi env values sebelum save:
```php
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    throw new \Exception('Invalid email');
}
```

### 4. **Default Values**
Selalu provide default value:
```php
$host = tenant_env('router_host', '192.168.1.1');
```

### 5. **Documentation**
Dokumentasikan semua custom env keys yang digunakan tenant.

---

## Migration dari .env ke Database

Jika sebelumnya menggunakan `.env` per tenant, migrasi dengan:

```php
$tenant = \App\Tenant::where('domain', 'example.com')->first();

$tenant->env_variables = [
    'pppoe_password' => env('PPPOE_PASSWORD'),
    'google_maps_api_key' => env('GOOGLE_MAPS_API_KEY'),
    'mail_host' => env('MAIL_HOST'),
    'mail_username' => env('MAIL_USERNAME'),
    // ... copy semua env yang perlu per tenant
];

$tenant->save();
```

---

## Troubleshooting

### ENV tidak terbaca
```bash
# Clear all cache
php artisan cache:clear
php artisan config:clear

# Restart PHP-FPM
systemctl restart php-fpm
```

### Check ENV value saat runtime
```php
// Debug di controller atau tinker
dd([
    'tenant' => tenant_env('key'),
    'config' => config('tenant.key'),
    'env' => env('KEY'),
]);
```

### Verify tenant ENV in database
```sql
SELECT domain, env_variables 
FROM tenants 
WHERE domain = 'your-domain.com';
```

---

## Kesimpulan

✅ Setiap tenant bisa punya ENV variables berbeda  
✅ Tidak perlu file `.env` per tenant  
✅ Mudah manage via database/UI  
✅ Auto-encryption untuk sensitive data  
✅ Fallback ke `.env` global jika tidak di-set  
✅ Caching untuk performance  

Sistem ENV per tenant sudah **production-ready**! 🚀
