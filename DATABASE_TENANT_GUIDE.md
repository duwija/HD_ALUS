# Sistem Multi-Tenant dengan Database Master

Sistem telah diupgrade menggunakan **Database Master** untuk menyimpan konfigurasi tenant.

## Keuntungan Sistem Baru

✅ **Tambah tenant tanpa edit file** - Via command atau API
✅ **Real-time updates** - Tidak perlu restart/reload
✅ **Caching otomatis** - Performa tetap cepat
✅ **Keamanan lebih baik** - Password & token terenkripsi
✅ **Audit trail** - Track perubahan tenant
✅ **Scalable** - Mudah manage ratusan tenant
✅ **API-ready** - Siap integrasi dengan sistem lain

---

## Struktur Database

### Master Database: `isp_master`

Tabel `tenants`:
- `id` - ID tenant
- `domain` - Domain tenant (unique)
- `app_name` - Nama aplikasi
- `signature` - Signature tenant
- `rescode` - Kode tenant 2-3 huruf (unique)
- `db_host`, `db_port`, `db_database` - Konfigurasi database
- `db_username`, `db_password` - Kredensial (encrypted)
- `mail_from` - Email sender
- `whatsapp_token`, `xendit_key` - Token integrasi (encrypted)
- `features` - JSON fitur yang diaktifkan
- `is_active` - Status aktif/non-aktif
- `notes` - Catatan tambahan
- `created_at`, `updated_at`, `deleted_at`

### Tenant Databases

Setiap tenant tetap punya database terpisah:
- `adiyasa_2.2`
- `kencana`
- `reseller1_isp`
- dll.

---

## Artisan Commands

### 1. List All Tenants

```bash
php artisan tenant:list

# Show all including inactive
php artisan tenant:list --all
```

Output:
```
+----+-----------------------+----------------+---------+---------------+--------+
| ID | Domain                | App Name       | Rescode | Database      | Active |
+----+-----------------------+----------------+---------+---------------+--------+
| 1  | adiyasa.alus.co.id    | ADIYASA        | AD      | adiyasa_2.2   | ✓      |
| 6  | kencana.alus.co.id    | KENCANA        | KC      | kencana       | ✓      |
+----+-----------------------+----------------+---------+---------------+--------+
```

### 2. Create New Tenant

**Cara 1: Interactive**
```bash
php artisan tenant:create newdomain.alus.co.id
# Akan ditanyakan: app name, rescode, database name, dll
```

**Cara 2: With Options**
```bash
php artisan tenant:create newdomain.alus.co.id \
  --app-name="New Tenant" \
  --rescode="NT" \
  --db-name="new_tenant_db" \
  --db-pass="password123" \
  --create-db
```

Options:
- `--app-name` - Nama aplikasi
- `--rescode` - Kode tenant (2-3 huruf)
- `--db-name` - Nama database
- `--db-user` - Username database (default: root)
- `--db-pass` - Password database
- `--create-db` - Buat database otomatis

### 3. Migrate from Config

Jika masih ada tenant di `config/tenants.php`:
```bash
php artisan tenant:migrate-from-config
```

---

## Cara Menambah Tenant Baru

### Metode 1: Via Artisan Command (RECOMMENDED)

```bash
# 1. Create tenant
php artisan tenant:create maharani.alus.co.id \
  --app-name="MAHARANI" \
  --rescode="MR" \
  --db-name="maharani_db" \
  --db-pass="Abc234def1!@" \
  --create-db

# 2. Create storage directories
mkdir -p storage/tenants/MR/{logs,app/public}
mkdir -p public/tenants/MR/{storage,upload,backup,users}
chown -R nginx:nginx storage/tenants/MR public/tenants/MR

# 3. Import database structure (optional)
mysqldump -u root -p --no-data adiyasa_2.2 | mysql -u root -p maharani_db

# 4. Create nginx config
cat > /etc/nginx/conf.d/maharani.alus.co.id.conf << 'EOF'
server {
    listen 80;
    server_name maharani.alus.co.id;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl;
    server_name maharani.alus.co.id;
    root /var/www/kencana.alus.co.id/public;
    index index.php index.html;

    location ~ \.php$ {
        include /etc/nginx/fastcgi_params;
        fastcgi_pass unix:/run/php-fpm/www.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_index index.php;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    ssl_certificate /etc/letsencrypt/live/maharani.alus.co.id/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/maharani.alus.co.id/privkey.pem;
}
EOF

# 5. Setup SSL
certbot --nginx -d maharani.alus.co.id

# 6. Test dan reload nginx
nginx -t && systemctl reload nginx

# 7. Point DNS
# Arahkan maharani.alus.co.id ke IP server

# 8. Test
curl -I https://maharani.alus.co.id
```

### Metode 2: Via MySQL Direct

```sql
INSERT INTO isp_master.tenants (
    domain, app_name, signature, rescode,
    db_host, db_port, db_database, db_username, db_password,
    mail_from, features, is_active,
    created_at, updated_at
) VALUES (
    'newdomain.com',
    'NEW TENANT',
    'New Tenant Network',
    'NT',
    '127.0.0.1',
    '3306',
    'new_tenant_db',
    'root',
    AES_ENCRYPT('password', 'encryption_key'),
    'admin@newdomain.com',
    '{"accounting":true,"ticketing":true,"whatsapp":true,"payment_gateway":true}',
    1,
    NOW(),
    NOW()
);
```

**Note:** Password akan otomatis dienkripsi oleh Laravel Model.

---

## Update Tenant

### Via MySQL

```sql
-- Update app name
UPDATE isp_master.tenants 
SET app_name = 'NEW NAME', updated_at = NOW()
WHERE domain = 'domain.com';

-- Disable tenant
UPDATE isp_master.tenants 
SET is_active = 0
WHERE domain = 'domain.com';

-- Update features
UPDATE isp_master.tenants 
SET features = '{"accounting":false,"ticketing":true}'
WHERE domain = 'domain.com';
```

**Jangan lupa clear cache:**
```bash
php artisan cache:clear
```

---

## Delete Tenant

```sql
-- Soft delete (recommended)
UPDATE isp_master.tenants 
SET deleted_at = NOW(), is_active = 0
WHERE domain = 'domain.com';

-- Hard delete (permanent)
DELETE FROM isp_master.tenants WHERE domain = 'domain.com';
```

**Manual cleanup:**
```bash
# Drop database
mysql -u root -p -e "DROP DATABASE tenant_db;"

# Remove storage
rm -rf storage/tenants/[RESCODE]
rm -rf public/tenants/[RESCODE]

# Remove nginx config
rm /etc/nginx/conf.d/domain.com.conf
nginx -t && systemctl reload nginx

# Revoke SSL
certbot delete --cert-name domain.com
```

---

## Caching

Sistem menggunakan Laravel Cache untuk performa:

- Cache key: `tenant:{domain}`
- TTL: 3600 seconds (1 jam)
- Auto-clear saat tenant update/delete

**Manual clear cache:**
```bash
php artisan cache:clear
```

**Clear specific tenant cache via tinker:**
```bash
php artisan tinker
>>> Cache::forget('tenant:domain.com');
>>> Cache::forget('tenant:rescode:NT');
```

---

## Troubleshooting

### Error: "Tenant not found"
```bash
# Check tenant exists
php artisan tenant:list

# Check database connection
mysql -u root -p isp_master -e "SELECT * FROM tenants WHERE domain='domain.com';"

# Clear cache
php artisan cache:clear
```

### Error: Database connection failed
```bash
# Check credentials
php artisan tinker
>>> \App\Tenant::find(1)->db_password  # Will be decrypted

# Test connection
mysql -h DB_HOST -u DB_USER -p DB_NAME
```

### Tenant not loading after update
```bash
# Clear tenant cache
php artisan cache:clear

# Restart PHP-FPM
systemctl restart php-fpm
```

---

## Backup & Restore

### Backup Master Database

```bash
mysqldump -u root -p isp_master > backup_master_$(date +%Y%m%d).sql
```

### Restore Master Database

```bash
mysql -u root -p isp_master < backup_master_20251116.sql
php artisan cache:clear
```

---

## Migration dari Config File

Jika ingin kembali ke config file:

```bash
# 1. Restore backup
cp backups/tenant_config_20251115_110453/tenants.php config/
cp backups/tenant_config_20251115_110453/TenantMiddleware.php app/Http/Middleware/

# 2. Update TenantMiddleware untuk tidak query database
# Edit app/Http/Middleware/TenantMiddleware.php

# 3. Clear cache
php artisan config:clear
php artisan cache:clear
```

---

## API Integration (Future)

Sistem sudah siap untuk ditambahkan API endpoint:

```php
// routes/api.php
Route::middleware('auth:api')->group(function () {
    Route::get('/tenants', 'TenantController@index');
    Route::post('/tenants', 'TenantController@store');
    Route::get('/tenants/{id}', 'TenantController@show');
    Route::put('/tenants/{id}', 'TenantController@update');
    Route::delete('/tenants/{id}', 'TenantController@destroy');
});
```

---

## Security Notes

1. ✅ Database password terenkripsi dengan Laravel Encryption
2. ✅ WhatsApp token & Xendit key terenkripsi
3. ✅ Master database terpisah dari tenant
4. ✅ Soft delete untuk audit trail
5. ⚠️ Pastikan `APP_KEY` di `.env` tidak berubah (atau data encrypted tidak bisa didekripsi)

---

## Performance

- **Caching:** Config tenant di-cache 1 jam
- **Connection pooling:** Koneksi database di-reuse
- **Lazy loading:** Database tenant hanya connect saat dibutuhkan
- **Fallback:** Jika database master down, fallback ke config file

---

## File Penting

- **Master DB Config:** `config/database.php` (connection 'master')
- **Tenant Model:** `app/Tenant.php`
- **Middleware:** `app/Http/Middleware/TenantMiddleware.php`
- **Commands:** `app/Console/Commands/Tenant*.php`
- **Migration:** `database/migrations/2025_11_16_*_create_tenants_table.php`

---

Untuk pertanyaan atau issue, hubungi tim development.
