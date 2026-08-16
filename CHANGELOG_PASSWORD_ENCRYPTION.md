# CHANGELOG - Password Encryption (Distrouter, OLT, Tenant DB)

## [1.0.0] - 2026-08-16

### 🔒 Enkripsi Password At-Rest untuk Distrouter, OLT, dan Tenant DB Password

Sebelumnya password MikroTik (`distrouters.password`), password Telnet OLT
(`olts.password`), dan password MySQL tenant (`isp_master.tenants.db_password`)
tersimpan **plaintext** di database. Perubahan ini mengenkripsinya at-rest
menggunakan `APP_KEY` Laravel (`Crypt::encryptString()` / cast `encrypted`),
transparan untuk semua kode yang sudah ada (`$distrouter->password`,
`$olt->password`, `$tenant->db_password` tetap mengembalikan plaintext saat
dibaca — enkripsi/dekripsi terjadi otomatis).

#### Added
- `php artisan distrouter:encrypt-passwords` — enkripsi `distrouters.password` di semua tenant aktif (loop switch DB per tenant, sama seperti `TenantDatabaseSwitcher`).
- `php artisan olt:encrypt-passwords` — enkripsi `olts.password` di semua tenant aktif. Otomatis memperbesar kolom ke `VARCHAR(255)` dulu (aslinya `VARCHAR(50)`, terlalu kecil untuk ciphertext ~200+ karakter).
- `php artisan tenant:encrypt-db-passwords` — enkripsi `isp_master.tenants.db_password` (satu database saja, tidak perlu loop tenant). Otomatis memperbesar kolom ke `VARCHAR(255)` (aslinya `VARCHAR(191)` — inilah alasan enkripsi field ini dulu dibatalkan, lihat komentar lama di `app/Tenant.php`).
- Semua tiga command punya opsi yang sama:
  - `--dry-run` — simulasi, tidak mengubah data, cuma menghitung berapa baris yang akan dienkripsi.
  - `--tenant=domain` — filter ke satu tenant saja (hanya untuk `distrouter:encrypt-passwords` / `olt:encrypt-passwords`, karena `tenant:encrypt-db-passwords` beroperasi di `isp_master` yang tunggal).
  - `--rollback` — kembalikan password ke plaintext asli dari backup table, lalu berhenti.
- Backup table otomatis dibuat per jenis data sebelum baris pertama diubah, memakai `insertOrIgnore` supaya baris lama **tidak pernah ditimpa** meski command dijalankan berkali-kali (jadi backup selalu berisi plaintext asli, bukan hasil run kedua/ketiga):
  - `distrouters_password_backup` (per-DB tenant)
  - `olts_password_backup` (per-DB tenant)
  - `tenants_db_password_backup` (di `isp_master`)
- Verifikasi round-trip: setiap baris yang dienkripsi langsung dibaca ulang dan di-decrypt untuk memastikan hasilnya identik dengan plaintext asli. Kalau gagal, command **berhenti total untuk tenant itu** (tidak lanjut ke baris berikutnya) dan mengarahkan untuk `--rollback` sebelum retry.
- Idempotent: baris yang gagal di-decrypt dengan `Crypt::decryptString()` dianggap masih plaintext dan diproses; baris yang berhasil di-decrypt dianggap sudah pernah dienkripsi dan di-skip. Aman dijalankan ulang berkali-kali (misal setelah error di tengah jalan, atau ada tenant baru).

#### Changed
- `app/Distrouter.php` — tambah `protected $casts = ['password' => 'encrypted']`. Semua kode existing yang baca/tulis `$distrouter->password` tidak perlu diubah.
- `app/Olt.php` — tambah `protected $casts = ['password' => 'encrypted']`, pola sama.
- `app/Tenant.php` — `setDbPasswordAttribute()` / `getDbPasswordAttribute()` sekarang encrypt/decrypt via `Crypt::encryptString()` / `Crypt::decryptString()`, mengikuti pola yang sudah ada di `whatsapp_token` dan `xendit_key`. Ada fallback ke raw value kalau decrypt gagal (menangani baris yang belum sempat termigrasi).

#### Security
- ⚠️ **Data lama tersimpan plaintext sampai command migrasi dijalankan.** Cast `encrypted` di `Distrouter`/`Olt` bersifat aktif segera setelah kode di-deploy — kalau command belum dijalankan untuk tenant tertentu, semua read Eloquent ke `distrouters`/`olts` tenant itu akan gagal dengan `DecryptException` (job seperti `EnableMikrotikJob`, `IsolirJob`, sinkronisasi PPPoE, koneksi SNMP OLT bisa langsung error). Lihat **Urutan Deploy** di bawah — ini bagian paling kritis dari perubahan ini.
- Backup table (`*_password_backup`) menyimpan **plaintext asli** sebagai jaring pengaman rollback. Ini sengaja, tapi berarti ada salinan plaintext yang tertinggal di database — lihat **Cleanup** di bawah.

---

## 🚀 Urutan Deploy (WAJIB diikuti urutannya)

Intinya: **jalankan command enkripsi SEBELUM cast/encryptor aktif membaca data**, supaya tidak ada jendela waktu di mana kode baru mencoba men-decrypt data yang masih plaintext.

### Opsi A — Deploy 2 tahap (paling aman, direkomendasikan untuk production)

1. **Tahap 1**: Deploy HANYA 3 file command baru (`app/Console/Commands/EncryptDistrouterPasswords.php`, `EncryptOltPasswords.php`, `EncryptTenantDbPasswords.php`) — **jangan** sertakan perubahan di `app/Distrouter.php`, `app/Olt.php`, `app/Tenant.php` dulu.
2. Jalankan dry-run dulu untuk lihat cakupan data:
   ```bash
   php artisan distrouter:encrypt-passwords --dry-run
   php artisan olt:encrypt-passwords --dry-run
   php artisan tenant:encrypt-db-passwords --dry-run
   ```
3. Uji di satu tenant kecil dulu (bukan dry-run, benar-benar jalan):
   ```bash
   php artisan distrouter:encrypt-passwords --tenant=contoh.domain.com
   php artisan olt:encrypt-passwords --tenant=contoh.domain.com
   ```
   Cek aplikasi tenant itu masih bisa connect ke MikroTik/OLT seperti biasa (password masih terbaca plaintext dari kode lama karena cast belum aktif).
4. Kalau aman, jalankan untuk **semua tenant**:
   ```bash
   php artisan distrouter:encrypt-passwords
   php artisan olt:encrypt-passwords
   php artisan tenant:encrypt-db-passwords
   ```
   Perhatikan output tiap tenant — pastikan tidak ada baris `BERHENTI:` (kalau ada, tenant itu perlu `--rollback` lalu diselidiki sebelum lanjut).
5. **Tahap 2**: Deploy perubahan `app/Distrouter.php`, `app/Olt.php`, `app/Tenant.php` (cast + mutator/accessor). Karena semua data sudah terenkripsi di tahap 1, tidak ada `DecryptException`.
6. Verifikasi pasca-deploy (lihat bagian **Verifikasi** di bawah).

### Opsi B — Deploy sekaligus (kalau tidak bisa split deploy)

Kalau semua perubahan (command + model) harus naik dalam satu deploy yang sama:

1. **Stop dulu queue worker & cron** untuk tenant-tenant yang terpengaruh (supervisorctl stop, atau `php artisan down` sesaat) — supaya tidak ada proses yang baca `distrouters`/`olts` di antara deploy dan command selesai jalan.
2. Deploy kode.
3. **Segera** jalankan ketiga command untuk semua tenant (lihat langkah 4 di Opsi A).
4. Baru start lagi queue worker & cron.

Opsi A lebih aman karena tidak perlu downtime sama sekali; Opsi B risikonya ada gap singkat di mana job yang jalan bisa gagal untuk tenant yang belum sempat di-encrypt.

---

## ✅ Verifikasi

```bash
php artisan tinker
```
```php
// Pastikan password terbaca plaintext seperti biasa (bukan ciphertext)
$d = App\Distrouter::first();
$d->password; // harus plaintext, bukan string base64 panjang

$o = App\Olt::first();
$o->password; // harus plaintext

$t = App\Tenant::on('isp_master')->first();
$t->db_password; // harus plaintext
$t->toTenantArray()['db_password']; // sama, dipakai TenantDatabaseSwitcher
```

Cek juga secara fungsional:
- Enable/disable PPPoE customer via `Distrouter::mikrotik_enable()` / `mikrotik_disable()` masih berhasil connect ke MikroTik.
- Job SNMP OLT (walk/pull data OLT) masih berhasil connect.
- `TenantDatabaseSwitcher::switchTo()` masih berhasil switch koneksi ke DB tenant manapun (dia sudah pakai `toTenantArray()` yang otomatis lewat accessor terenkripsi).
- Cek `storage/logs/laravel.log` beberapa saat setelah deploy, cari `DecryptException` — kalau muncul, berarti ada tenant yang datanya belum sempat di-encrypt (jalankan command untuk tenant tsb).

---

## ⏪ Rollback

Kalau terjadi masalah setelah deploy:

```bash
# Kembalikan password ke plaintext dari backup (per jenis data)
php artisan distrouter:encrypt-passwords --rollback
php artisan olt:encrypt-passwords --rollback
php artisan tenant:encrypt-db-passwords --rollback

# Lalu revert perubahan kode (cast/mutator) supaya aplikasi baca plaintext lagi
git revert <commit-hash-perubahan-model>
```

Rollback per-tenant juga bisa: `php artisan distrouter:encrypt-passwords --tenant=domain --rollback`.

---

## 🧹 Cleanup (setelah production stabil, misal 1-2 minggu tanpa masalah)

Backup table menyimpan **plaintext** kredensial sebagai jaring pengaman sementara — jangan dibiarkan selamanya:

```sql
-- Jalankan di tiap DB tenant setelah yakin tidak perlu rollback lagi
DROP TABLE IF EXISTS distrouters_password_backup;
DROP TABLE IF EXISTS olts_password_backup;

-- Jalankan di isp_master
DROP TABLE IF EXISTS tenants_db_password_backup;
```

---

## Files Modified
1. `app/Distrouter.php` — cast `password` → `encrypted`
2. `app/Olt.php` — cast `password` → `encrypted`
3. `app/Tenant.php` — `setDbPasswordAttribute()` / `getDbPasswordAttribute()` sekarang encrypt/decrypt

## Files Created
1. `app/Console/Commands/EncryptDistrouterPasswords.php`
2. `app/Console/Commands/EncryptOltPasswords.php`
3. `app/Console/Commands/EncryptTenantDbPasswords.php`
4. `CHANGELOG_PASSWORD_ENCRYPTION.md` — file ini
