# Tenant Database Migration Guide

Panduan ini menjelaskan cara menjalankan database migration ke semua tenant yang terdaftar, baik melalui **Admin Panel** (UI) maupun **Terminal** (CLI).

---

## Apa itu Tenant Migration?

Aplikasi ini menggunakan arsitektur **multi-tenant**: setiap tenant memiliki database MySQL terpisah. Ketika ada perubahan skema database (file baru di `database/migrations/`), migration harus dijalankan ke **semua database tenant** secara terpisah.

---

## Cara 1: Melalui Admin Panel (Direkomendasikan)

### Akses Halaman

1. Login ke Admin Panel: `https://domain-anda/admin`
2. Klik menu **DB Migration** di sidebar kiri

### Opsi yang Tersedia

| Opsi | Keterangan |
|------|-----------|
| **Filter Tenant** | Pilih satu tenant tertentu, atau kosongkan untuk semua tenant aktif |
| **Include Tenant Non-Aktif** | Centang jika ingin migrate tenant yang sudah dinonaktifkan |
| **Dry Run** *(default: aktif)* | Tampilkan SQL yang akan dieksekusi tanpa benar-benar menjalankannya |

### Langkah Kerja yang Aman

1. Pastikan **Dry Run** dicentang (default)
2. Klik **Jalankan Migrasi** → lihat SQL yang akan dijalankan per tenant
3. Periksa output — pastikan tidak ada error
4. Matikan centang **Dry Run**
5. Klik **Jalankan Migrasi** kembali untuk eksekusi sesungguhnya
6. Cek ringkasan: Berhasil / Gagal / Dilewati

### Interpretasi Status

| Status | Arti |
|--------|------|
| ✅ **SUCCESS** | Migration berjalan atau "Nothing to migrate" (sudah up-to-date) |
| ❌ **ERROR** | Migration gagal — lihat output error |
| ⚠️ **SKIPPED** | Konfigurasi DB tenant tidak lengkap |

---

## Cara 2: Melalui Terminal (CLI)

```bash
cd /var/www/kencana.alus.co.id
```

### Perintah Dasar

```bash
# Migrate semua tenant aktif
php artisan tenant:migrate-all

# Dry-run (lihat SQL tanpa eksekusi)
php artisan tenant:migrate-all --pretend

# Hanya untuk satu tenant (by domain, rescode, atau ID)
php artisan tenant:migrate-all --tenant=KC
php artisan tenant:migrate-all --tenant=kencana.alus.co.id
php artisan tenant:migrate-all --tenant=6

# Termasuk tenant non-aktif
php artisan tenant:migrate-all --include-inactive

# Hanya satu file migration tertentu
php artisan tenant:migrate-all --path=database/migrations/2026_04_01_xxx.php
```

### Contoh Output Berhasil

```
Starting tenant migrations...

Migrating tenant: 6 | kencana.alus.co.id | kencana
  Nothing to migrate.
  OK

Tenant migration finished.
Success : 1
Failed  : 0
```

---

## Workflow Deployment (Server Baru / Update Aplikasi)

### Saat Deploy Aplikasi ke Server Baru

```bash
# 1. Jalankan migration untuk database master (isp_master)
php artisan migrate --database=isp_master

# 2. Daftarkan tenant baru melalui Admin Panel → Tenant Management

# 3. Jalankan migration ke semua tenant
php artisan tenant:migrate-all
```

### Saat Ada Migration Baru (Update Rutin)

```bash
# Pull code terbaru
git pull

# Dry-run dulu
php artisan tenant:migrate-all --pretend

# Jika aman, jalankan
php artisan tenant:migrate-all
```

---

## Troubleshooting

### Error: Access Denied

```
SQLSTATE[HY000] [1698] Access denied for user 'root'@'localhost'
```

**Penyebab**: Kredensial database di tabel `tenants` pada `isp_master` tidak valid.

**Solusi**: Update data tenant di Admin Panel → Tenant Management → Edit → perbaiki `db_username`/`db_password`.

### Error: Table Already Exists

```
SQLSTATE[42S01]: Base table or view already exists
```

**Penyebab**: Database tenant sudah memiliki tabel tersebut tapi tidak ada rekaman di tabel `migrations`.

**Solusi**: Tambahkan guard `Schema::hasTable()` di migration yang bermasalah, atau jalankan:
```bash
php artisan migrate:status --database=mysql
```
dengan credentials tenant yang dimaksud.

### Error: Class Already Declared

```
Cannot declare class CreateXxxTable, because the name is already in use
```

**Penyebab**: Ada dua file migration dengan nama class yang sama di `database/migrations/`. Biasanya konflik antara file custom dan vendor (misal Sanctum).

**Solusi**: Pastikan `Sanctum::ignoreMigrations()` dipanggil di method `register()` (bukan `boot()`) di `AppServiceProvider`, dan file migration custom tidak menggunakan nama class yang sama dengan vendor.

---

## Arsitektur Teknis

```
Admin Panel (UI)
  └─ POST /admin/migrate/run
        └─ AdminMigrateController::run()
              └─ per tenant: new Process(['php', 'artisan', 'migrate', ...])
                    └─ subprocess terpisah per tenant
                          (env: DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD)

CLI
  └─ php artisan tenant:migrate-all
        └─ TenantMigrateAll.php (app/Console/Commands/)
              └─ sama: subprocess per tenant
```

**Mengapa subprocess?** Jika migration dijalankan dalam satu PHP process, class PHP dari file migration akan dideklarasikan lebih dari sekali dan menyebabkan `Fatal Error`. Subprocess memastikan setiap tenant berjalan dalam proses PHP yang bersih dan terisolasi.

---

## Files Terkait

| File | Fungsi |
|------|--------|
| `app/Console/Commands/TenantMigrateAll.php` | Artisan command `tenant:migrate-all` |
| `app/Http/Controllers/Admin/AdminMigrateController.php` | Controller API untuk Admin Panel |
| `resources/views/admin/migrate/index.blade.php` | Halaman UI di Admin Panel |
| `app/Tenant.php` | Model tenant dari database `isp_master` |
| `database/migrations/` | Semua file migration (untuk semua tenant DB) |
