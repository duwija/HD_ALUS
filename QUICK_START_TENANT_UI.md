# Quick Start: Tenant Management UI

## Akses Cepat
```
URL: https://kencana.alus.co.id/admin/tenants
```

## Menu Tersedia
1. **Daftar Tenant** - Lihat semua tenant yang ada
2. **Tambah Tenant** - Buat tenant baru
3. **Edit Tenant** - Ubah konfigurasi tenant
4. **Detail Tenant** - Lihat informasi lengkap tenant
5. **Toggle Status** - Active/Inactive tenant
6. **Hapus Tenant** - Soft delete tenant

## Fitur Auto-Create
✅ Buat database otomatis  
✅ Import struktur database  
✅ Buat folder storage & public  
✅ Set privilege user database  

## Yang Masih Manual
⚠️ Konfigurasi nginx  
⚠️ SSL Certificate (Let's Encrypt)  
⚠️ DNS Configuration  

## Cara Cepat Tambah Tenant

### 1. Login ke aplikasi
### 2. Klik "Tenant Management" di menu
### 3. Klik "Tambah Tenant Baru"
### 4. Isi form:
```
Domain: client.alus.co.id
App Name: PT CLIENT INTERNET
Rescode: client
Email: no-reply@client.alus.co.id
Database: isp_client
DB User: client_user
DB Password: ********
☑ Auto Create Database
```

### 5. Pilih Features:
```
☑ Accounting
☑ Ticketing  
☑ WhatsApp
☐ Payment Gateway
```

### 6. Submit
Sistem akan otomatis:
- Membuat database `isp_client`
- Membuat user `client_user` 
- Import struktur database
- Membuat folder storage
- Simpan ke master DB

### 7. Konfigurasi Manual

#### A. Nginx Config
```bash
# Buat file config
nano /etc/nginx/sites-available/client.alus.co.id

# Copy dari config tenant lain, ganti domain
# ...

# Enable site
ln -s /etc/nginx/sites-available/client.alus.co.id /etc/nginx/sites-enabled/
nginx -t
systemctl reload nginx
```

#### B. SSL Certificate
```bash
certbot --nginx -d client.alus.co.id
```

#### C. DNS Setting
Arahkan `client.alus.co.id` A record ke IP server

### 8. Test
```bash
curl -I https://client.alus.co.id
```

Atau buka di browser.

## Environment Variables

### Menambah Custom ENV via Tinker
```bash
php artisan tinker

$tenant = App\Tenant::where('rescode', 'client')->first();
$tenant->env_variables = [
    'API_KEY' => 'your-key',
    'MAX_UPLOAD' => '10M'
];
$tenant->save();
```

### Akses di Code
```php
$key = tenant_env('API_KEY');
```

## Artisan Commands (CLI Alternative)

```bash
# List semua tenant
php artisan tenant:list

# Buat tenant via CLI (interactive)
php artisan tenant:create

# Migrate dari config lama
php artisan tenant:migrate-from-config
```

## Troubleshooting

### Toggle Status tidak bekerja
- Cek console browser (F12)
- Clear browser cache
- Pastikan jQuery loaded

### Database creation failed
```bash
# Grant privilege ke MySQL user
mysql -u root -p
GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost';
FLUSH PRIVILEGES;
```

### Permission denied
```bash
chown -R www-data:www-data storage/ public/
chmod -R 775 storage/
```

## Support
- Check logs: `storage/logs/laravel.log`
- Nginx logs: `/var/log/nginx/error.log`
- Dokumentasi lengkap: `TENANT_MANAGEMENT_UI_GUIDE.md`

---
**Dibuat:** 2025-01-16  
**Update:** v1.0
