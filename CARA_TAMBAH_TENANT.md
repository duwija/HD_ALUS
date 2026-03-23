# Panduan Menambah Tenant Baru

Sistem ini menggunakan multi-tenancy dengan:
- **Database terpisah per tenant**
- **Storage & logs terpisah**
- **ENV variables dinamis per tenant**
- **Konfigurasi tersimpan di database master**

---

## Metode 1: Artisan Command (RECOMMENDED) 🚀

**Paling mudah dan cepat!**

```bash
cd /var/www/kencana.alus.co.id

php artisan tenant:create newdomain.com \
  --app-name="Tenant Name" \
  --rescode="TN" \
  --db-name="tenant_db" \
  --db-pass="password123" \
  --create-db
```

**Keuntungan:**
- ✅ Otomatis buat database
- ✅ Langsung masuk ke database master
- ✅ Tidak perlu edit file
- ✅ Tidak perlu restart service
- ✅ Validasi otomatis

**Setelah command selesai:**
1. Buat direktori storage & public
2. Setup nginx config
3. Setup SSL certificate
4. Pointing DNS

---

## Metode 2: Script Bash

```bash
cd /var/www/kencana.alus.co.id
./add-tenant.sh
```

Script akan meminta input:
- Domain tenant
- Tenant ID (angka unik)
- Nama aplikasi
- Signature
- Rescode (2-3 huruf unik)
- Nama database
- Email from

Script otomatis akan:
1. Membuat database baru
2. Import struktur database
3. Membuat direktori storage/logs
4. Membuat nginx config
5. Setup SSL certificate
6. Generate konfigurasi untuk `config/tenants.php`

**Setelah script selesai:**
1. Copy konfigurasi yang ditampilkan ke `config/tenants.php`
2. Jalankan cache clear
3. Pointing DNS

---

## Metode 2: Manual

### LANGKAH 1: Buat Database Baru

```bash
# Masuk ke MySQL
mysql -u root -p

# Buat database
CREATE DATABASE nama_tenant_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

### LANGKAH 2: Import Struktur Database

Import struktur dari tenant yang sudah ada (tanpa data):

```bash
# Export struktur dari tenant adiyasa (tanpa data)
mysqldump -u root -p --no-data adiyasa_2.2 > struktur_tenant.sql

# Import ke database baru
mysql -u root -p nama_tenant_db < struktur_tenant.sql

# Atau langsung dengan pipe
mysqldump -u root -p --no-data adiyasa_2.2 | mysql -u root -p nama_tenant_db
```

### LANGKAH 3: Buat Direktori Storage dan Public Tenant

```bash
cd /var/www/kencana.alus.co.id

# Buat direktori storage dengan rescode tenant (contoh: RS)
mkdir -p storage/tenants/RS/logs
mkdir -p storage/tenants/RS/app/public

# Buat direktori public untuk tenant (PENTING!)
mkdir -p public/tenants/RS/storage
mkdir -p public/tenants/RS/upload
mkdir -p public/tenants/RS/backup
mkdir -p public/tenants/RS/users

# Set permission
chown -R nginx:nginx storage/tenants/RS
chown -R nginx:nginx public/tenants/RS
chmod -R 755 storage/tenants/RS
chmod -R 755 public/tenants/RS
```

**Catatan:** Setiap tenant memiliki folder terpisah untuk:
- `storage/tenants/[RESCODE]/` - File private (logs, cache, dll)
- `public/tenants/[RESCODE]/storage/` - File storage public
- `public/tenants/[RESCODE]/upload/` - File upload user
- `public/tenants/[RESCODE]/backup/` - File backup
- `public/tenants/[RESCODE]/users/` - File terkait user

### LANGKAH 4: Tambahkan Konfigurasi Tenant

Edit file `config/tenants.php`, tambahkan entry baru di array `'list'` sebelum penutup `],`:

```php
        // Tenant: Nama Tenant Baru
        'domain.tenant.com' => [
            'tenant_id' => 5,  // ID unik, increment dari tenant terakhir
            'domain' => 'domain.tenant.com',
            'app_name' => 'NAMA TENANT',
            'signature' => 'Signature Tenant',
            'rescode' => 'RS',  // 2-3 huruf UNIK (untuk nama folder storage)
            
            // Database Config
            'db_host' => '127.0.0.1',
            'db_port' => '3306',
            'db_database' => 'nama_tenant_db',  // Sesuaikan dengan database yang dibuat
            'db_username' => 'root',
            'db_password' => 'Abc234def1!@',
            
            // Mail Config
            'mail_from' => 'admin@domain.tenant.com',
            
            // WhatsApp Config (opsional)
            'whatsapp_token' => null,
            
            // Payment Gateway (opsional)
            'xendit_key' => null,
            
            // Features enabled
            'features' => [
                'accounting' => true,
                'ticketing' => true,
                'whatsapp' => true,
                'payment_gateway' => true,
            ],
        ],
```

**Pastikan:**
- `tenant_id` unik (tidak ada yang sama)
- `rescode` unik (tidak ada yang sama)
- `db_database` sesuai dengan database yang sudah dibuat

### LANGKAH 5: Buat Nginx Configuration

```bash
# Buat file config nginx
nano /etc/nginx/conf.d/domain.tenant.com.conf
```

Isi dengan:

```nginx
server {
    listen 80;
    server_name domain.tenant.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl;
    server_name domain.tenant.com;

    root /var/www/kencana.alus.co.id/public;
    index index.php index.html;

    location ~ \.php$ {
        include /etc/nginx/fastcgi_params;
        fastcgi_pass unix:/run/php-fpm/www.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        fastcgi_index index.php;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ /\.ht {
        deny all;
    }

    # SSL akan ditambahkan oleh certbot
    ssl_certificate /etc/letsencrypt/live/domain.tenant.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/domain.tenant.com/privkey.pem;
}
```

**Note:** Jika belum setup SSL, comment dulu 2 baris ssl_certificate.

### LANGKAH 6: Setup SSL Certificate

```bash
# Install/renew SSL dengan Let's Encrypt
certbot --nginx -d domain.tenant.com

# Atau jika certbot sudah auto-configure nginx
certbot certonly --nginx -d domain.tenant.com
```

### LANGKAH 7: Test dan Reload Nginx

```bash
# Test konfigurasi nginx
nginx -t

# Jika OK, reload nginx
systemctl reload nginx
```

### LANGKAH 8: Clear Laravel Cache

```bash
cd /var/www/kencana.alus.co.id
php artisan config:clear
php artisan cache:clear
```

### LANGKAH 9: Pointing DNS

Arahkan A record domain ke IP server:

```
Type: A
Name: domain.tenant.com
Value: [IP_SERVER]
TTL: 3600
```

### LANGKAH 10: Test Akses

Buka browser dan akses:
```
https://domain.tenant.com
```

Seharusnya muncul halaman login tenant baru.

---

## Checklist Penambahan Tenant

- [ ] Database dibuat
- [ ] Struktur database diimport
- [ ] Direktori storage dibuat (`storage/tenants/[RESCODE]`)
- [ ] Direktori public dibuat (`public/tenants/[RESCODE]/{storage,upload,backup,users}`)
- [ ] Permission storage sudah benar (nginx:nginx, 755)
- [ ] Permission public sudah benar (nginx:nginx, 755)
- [ ] Konfigurasi ditambahkan ke `config/tenants.php`
- [ ] `tenant_id` dan `rescode` unik
- [ ] Nginx config dibuat (`/etc/nginx/conf.d/`)
- [ ] SSL certificate sudah setup
- [ ] Nginx test OK (`nginx -t`)
- [ ] Nginx sudah direload
- [ ] Laravel cache cleared
- [ ] DNS pointing ke server
- [ ] Test akses domain berhasil

---

## Troubleshooting

### Error: "Tenant not found for domain"
- Cek domain di `config/tenants.php` sudah benar
- Jalankan `php artisan config:clear`

### Error: Database connection failed
- Cek nama database di config
- Pastikan database sudah dibuat
- Cek username/password MySQL

### Error: Permission denied di storage
- Jalankan: `chown -R nginx:nginx storage/tenants/[RESCODE]`
- Jalankan: `chmod -R 755 storage/tenants/[RESCODE]`

### Error: Permission denied di public
- Jalankan: `chown -R nginx:nginx public/tenants/[RESCODE]`
- Jalankan: `chmod -R 755 public/tenants/[RESCODE]`

### Error: File upload tidak tersimpan
- Cek folder `public/tenants/[RESCODE]/upload` sudah dibuat
- Cek permission folder (nginx:nginx, 755)

### SSL Error
- Pastikan domain sudah pointing ke server
- Tunggu propagasi DNS (bisa 5-30 menit)
- Test dengan: `nslookup domain.tenant.com`

### 404 Not Found
- Cek nginx config sudah benar
- Cek root path: `/var/www/kencana.alus.co.id/public`
- Reload nginx: `systemctl reload nginx`

---

## Contoh Lengkap

Menambah tenant untuk domain `maharani.alus.co.id`:

```bash
# 1. Buat database
mysql -u root -p -e "CREATE DATABASE maharani_db"

# 2. Import struktur
mysqldump -u root -p --no-data adiyasa_2.2 | mysql -u root -p maharani_db

# 3. Buat storage dan public folders
mkdir -p storage/tenants/MR/{logs,app/public}
mkdir -p public/tenants/MR/{storage,upload,backup,users}
chown -R nginx:nginx storage/tenants/MR
chown -R nginx:nginx public/tenants/MR

# 4. Edit config/tenants.php dan tambahkan
'maharani.alus.co.id' => [
    'tenant_id' => 5,
    'domain' => 'maharani.alus.co.id',
    'app_name' => 'MAHARANI',
    'rescode' => 'MR',
    'db_database' => 'maharani_db',
    ...
],

# 5. Buat nginx config
nano /etc/nginx/conf.d/maharani.alus.co.id.conf

# 6. Setup SSL
certbot --nginx -d maharani.alus.co.id

# 7. Test dan reload
nginx -t && systemctl reload nginx

# 8. Clear cache
php artisan config:clear && php artisan cache:clear

# 9. Pointing DNS maharani.alus.co.id ke IP server

# 10. Test akses
curl -I https://maharani.alus.co.id
```

---

## Tips

1. **Rescode:** Gunakan 2-3 huruf yang mudah diingat (AD=Adiyasa, KN=Kencana, MR=Maharani)
2. **Database:** Gunakan nama yang jelas (maharani_db, reseller1_isp, dll)
3. **Backup:** Selalu backup database sebelum testing
4. **Testing:** Test di local dulu dengan edit /etc/hosts sebelum DNS live
5. **Monitoring:** Cek log di `storage/tenants/[RESCODE]/logs/laravel.log`
6. **Public Files:** Setiap tenant memiliki folder terpisah untuk storage, upload, backup, dan users

---

## Struktur Folder per Tenant

```
/var/www/kencana.alus.co.id/
├── storage/
│   └── tenants/
│       └── [RESCODE]/           # Contoh: AD, KN, MR
│           ├── logs/
│           │   └── laravel.log
│           └── app/
│               └── public/
└── public/
    └── tenants/
        └── [RESCODE]/           # Contoh: AD, KN, MR
            ├── storage/         # File storage public
            ├── upload/          # File upload user
            ├── backup/          # File backup
            └── users/           # File terkait user
```

**Penjelasan:**
- `storage/tenants/[RESCODE]/` = File private (logs, cache, session)
- `public/tenants/[RESCODE]/` = File yang bisa diakses via web

---

## File-file Penting

- **Config Tenant:** `config/tenants.php`
- **Middleware:** `app/Http/Middleware/TenantMiddleware.php`
- **Helper:** `app/Helpers/TenantHelpers.php`
- **Nginx Config:** `/etc/nginx/conf.d/[domain].conf`
- **Storage Private:** `storage/tenants/[RESCODE]/`
- **Storage Public:** `public/tenants/[RESCODE]/`
- **Script Auto:** `add-tenant.sh`

---

Untuk pertanyaan lebih lanjut, hubungi tim development.

