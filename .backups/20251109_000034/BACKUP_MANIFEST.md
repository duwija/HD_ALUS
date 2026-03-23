# BACKUP MANIFEST - Perbaikan Laporan Keuangan
# Tanggal: 2025-11-09 00:00:34
# Backup Directory: .backups/20251109_000034

## INFORMASI BACKUP

**Backup ID:** 20251109_000034  
**Tanggal:** 2025-11-09 00:00:34  
**Tujuan:** Perbaikan perhitungan Neraca dan pembuatan Laba Rugi  
**Status:** READY - Backup selesai, siap untuk modifikasi

---

## FILES YANG DI-BACKUP

### 1. Controller
```
File: app/Http/Controllers/JurnalController.php
Backup: .backups/20251109_000034/JurnalController.php.backup
Size: ~2771 lines
Status: ✅ BACKED UP
Perubahan yang akan dilakukan:
  - Fix method neraca() (line 1723-1780)
  - Buat method labaRugi() baru
  - Fix helper methods
```

### 2. Routes
```
File: routes/web.php
Backup: .backups/20251109_000034/web.php.backup
Size: ~619 lines
Status: ✅ BACKED UP
Perubahan yang akan dilakukan:
  - Uncomment/fix route laba rugi
  - Tambah route export
```

### 3. Views
```
File: resources/views/jurnal/neraca.blade.php
Backup: .backups/20251109_000034/views/neraca.blade.php.backup
Status: ✅ BACKED UP
Perubahan: Update tampilan sesuai data baru

File: resources/views/jurnal/arus_kas.blade.php
Backup: .backups/20251109_000034/views/arus_kas.blade.php.backup
Status: ✅ BACKED UP
Perubahan: Minor improvements

File: resources/views/jurnal/neraca_saldo.blade.php
Backup: .backups/20251109_000034/views/neraca_saldo.blade.php.backup
Status: ✅ BACKED UP
Perubahan: Check consistency

File: resources/views/jurnal/laba_rugi.blade.php
Backup: N/A (file belum ada)
Status: ⚠️ WILL BE CREATED
Perubahan: Create new file
```

---

## CARA RESTORE

### Restore Single File
```bash
# Restore JurnalController
cd /var/www/html/adiyasa.alus.co.id
cp -p .backups/20251109_000034/JurnalController.php.backup app/Http/Controllers/JurnalController.php

# Restore routes
cp -p .backups/20251109_000034/web.php.backup routes/web.php

# Restore view tertentu
cp -p .backups/20251109_000034/views/neraca.blade.php.backup resources/views/jurnal/neraca.blade.php
```

### Restore All Files
```bash
cd /var/www/html/adiyasa.alus.co.id
BACKUP_DIR=".backups/20251109_000034"

# Restore controller
cp -p "$BACKUP_DIR/JurnalController.php.backup" app/Http/Controllers/JurnalController.php

# Restore routes
cp -p "$BACKUP_DIR/web.php.backup" routes/web.php

# Restore views
cp -p "$BACKUP_DIR/views/neraca.blade.php.backup" resources/views/jurnal/neraca.blade.php
cp -p "$BACKUP_DIR/views/arus_kas.blade.php.backup" resources/views/jurnal/arus_kas.blade.php
cp -p "$BACKUP_DIR/views/neraca_saldo.blade.php.backup" resources/views/jurnal/neraca_saldo.blade.php

# Hapus file baru yang dibuat (jika ada)
rm -f resources/views/jurnal/laba_rugi.blade.php

echo "Restore completed to state: 2025-11-09 00:00:34"
```

### Restore ke State Sebelum Semua Perubahan
```bash
cd /var/www/html/adiyasa.alus.co.id
bash .backups/20251109_000034/restore_all.sh
```

---

## PERUBAHAN YANG AKAN DILAKUKAN

### Phase 1: Fix Neraca (CRITICAL)
**File:** `app/Http/Controllers/JurnalController.php`  
**Method:** `neraca()` - Line 1723-1780

**Masalah:**
1. ❌ Query menggunakan `$child->id` seharusnya `$child->akun_code`
2. ❌ whereBetween hanya periode, seharusnya kumulatif
3. ❌ Formula tidak bedakan debit/credit normal
4. ❌ Parent-child logic tidak sesuai database

**Perbaikan:**
```php
// SEBELUM (SALAH):
$saldo = DB::table('jurnals')
    ->where('id_akun', $child->id)  // SALAH: pakai id
    ->whereBetween('date', [$tanggalAwal, $tanggalAkhir])  // SALAH: hanya periode
    ->selectRaw('SUM(debet) as total_debet, SUM(kredit) as total_kredit')
    ->first();
$childSaldo = ($saldo->total_debet ?? 0) - ($saldo->total_kredit ?? 0);  // SALAH: semua formula sama

// SESUDAH (BENAR):
$saldo = DB::table('jurnals')
    ->where('id_akun', $akun->akun_code)  // BENAR: pakai akun_code
    ->where('date', '<=', $tanggalAkhir)  // BENAR: kumulatif
    ->whereNull('deleted_at')
    ->selectRaw('SUM(debet) as total_debet, SUM(kredit) as total_kredit')
    ->first();

// BENAR: bedakan normal debit vs credit
if (in_array($group, ['aktiva', 'aset'])) {
    $saldoAkhir = $debet - $kredit;  // Aset: debit normal
} else {
    $saldoAkhir = $kredit - $debet;  // Kewajiban/Ekuitas: credit normal
}
```

### Phase 2: Buat Laba Rugi (HIGH)
**File:** `app/Http/Controllers/JurnalController.php`  
**Method:** `labaRugi()` - NEW

**Yang dibuat:**
1. ✅ Method labaRugi() baru
2. ✅ Helper method getKategoriSaldo()
3. ✅ View laba_rugi.blade.php baru
4. ✅ Export PDF & Excel

**Logic:**
```php
// Pendapatan (kredit - debet)
$pendapatan = getKategoriSaldo(['pendapatan', 'pendapatan lainnya'], 'kredit');

// Beban (debet - kredit)
$beban = getKategoriSaldo(['beban', 'beban lainnya'], 'debet');
$hpp = getKategoriSaldo(['harga pokok penjualan'], 'debet');

// Laba Bersih
$labaBersih = $totalPendapatan - $totalBeban - $hpp;
```

### Phase 3: Update Routes
**File:** `routes/web.php`

**Yang diubah:**
```php
// Uncomment dan fix
Route::get('jurnal/labarugi', 'JurnalController@labaRugi');
Route::get('jurnal/labarugi/pdf', 'JurnalController@exportLabaRugiPdf');
Route::get('jurnal/labarugi/excel', 'JurnalController@exportLabaRugiExcel');
```

---

## VERIFICATION CHECKLIST

Setelah perubahan, verifikasi:

### Database Check
```sql
-- Cek akun yang dipakai di Neraca
SELECT DISTINCT a.akun_code, a.name, a.category, a.group
FROM akuns a
JOIN jurnals j ON j.id_akun = a.akun_code
WHERE a.deleted_at IS NULL
AND j.deleted_at IS NULL
AND a.group IN ('aktiva', 'kewajiban', 'ekuitas')
ORDER BY a.group, a.category;

-- Cek saldo kas untuk validasi
SELECT 
    a.name,
    SUM(j.debet - j.kredit) as saldo
FROM jurnals j
JOIN akuns a ON j.id_akun = a.akun_code
WHERE a.category = 'kas & bank'
AND j.deleted_at IS NULL
AND a.deleted_at IS NULL
GROUP BY a.akun_code, a.name;
```

### Application Test
- [ ] Akses /jurnal/neraca - harus tampil tanpa error
- [ ] Cek angka Neraca - Total Aset = Total Kewajiban + Ekuitas
- [ ] Akses /jurnal/labarugi - harus tampil tanpa error
- [ ] Cek angka Laba Rugi - konsisten dengan data
- [ ] Test export PDF - berhasil download
- [ ] Test export Excel - berhasil download

---

## ROLLBACK SCENARIOS

### Scenario 1: Neraca Error
```bash
# Restore hanya controller
cp -p .backups/20251109_000034/JurnalController.php.backup app/Http/Controllers/JurnalController.php
cp -p .backups/20251109_000034/views/neraca.blade.php.backup resources/views/jurnal/neraca.blade.php
```

### Scenario 2: Laba Rugi Error
```bash
# Hapus file baru, restore controller
rm -f resources/views/jurnal/laba_rugi.blade.php
cp -p .backups/20251109_000034/JurnalController.php.backup app/Http/Controllers/JurnalController.php
cp -p .backups/20251109_000034/web.php.backup routes/web.php
```

### Scenario 3: Full Rollback
```bash
# Restore semua
cd /var/www/html/adiyasa.alus.co.id
cp -p .backups/20251109_000034/JurnalController.php.backup app/Http/Controllers/JurnalController.php
cp -p .backups/20251109_000034/web.php.backup routes/web.php
cp -p .backups/20251109_000034/views/*.backup resources/views/jurnal/
rm -f resources/views/jurnal/laba_rugi.blade.php
```

---

## BACKUP HISTORY

### Previous Backups
- **20251108_223247** - Full project backup (130MB tar.gz)
  Location: /var/www/html/adiyasa_backup_20251108_223247.tar.gz

### Current Backup
- **20251109_000034** - Selective files backup (before fixes)
  Location: /var/www/html/adiyasa.alus.co.id/.backups/20251109_000034/

---

## NOTES

1. ✅ Backup menggunakan `cp -p` untuk preserve permissions
2. ✅ Semua backup ada timestamp
3. ✅ Bisa restore partial atau full
4. ✅ Script restore otomatis tersedia
5. ⚠️ Test di development dulu sebelum production

**Next Step:** Mulai implementasi perbaikan dengan monitoring setiap perubahan.
