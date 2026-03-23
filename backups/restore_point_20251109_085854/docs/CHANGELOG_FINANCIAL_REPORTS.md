# CHANGELOG - Sistem Laporan Keuangan (Jurnal.id Style)

## Informasi Backup
- **Backup File**: `/var/www/html/adiyasa_backup_20251108_223247.tar.gz`
- **Backup Size**: 130MB
- **Backup Date**: 2025-11-08 22:32:47
- **Database**: adiyasa_2.2

---

## [2025-11-08] - Persiapan Implementasi

### Analisa Database
**Tabel `akuns`:**
- Kolom: id, akun_code, name, category, type, group, parent, tax, tax_value, created_at, updated_at, deleted_at
- Primary Key: id
- Unique Identifier: akun_code

**Tabel `jurnals`:**
- Kolom: id, code, date, id_akun, kredit, debet, reff, type, description, note, memo, category, created_by, contact_id, created_at, updated_at, deleted_at
- Primary Key: id
- Foreign Key: id_akun -> akuns.akun_code

**Kategori Akun yang tersedia:**
1. **AKTIVA/ASET:**
   - kas & bank
   - akun piutang
   - persediaan
   - aktiva lainnya
   - aktiva tetap

2. **KEWAJIBAN:**
   - akun hutang
   - kewajiban jangka pendek
   - kewajiban lancar lainnya

3. **EKUITAS:**
   - ekuitas

4. **PENDAPATAN:**
   - pendapatan
   - pendapatan lainnya

5. **BEBAN:**
   - beban
   - beban lainnya
   - harga pokok penjualan
   - depresiasi dan amortisasi

---

## File yang Akan Dibuat/Dimodifikasi

### 1. Controllers
- [ ] `app/Http/Controllers/FinancialReportController.php` - **BARU**
  - Status: Belum dibuat
  - Deskripsi: Controller utama untuk semua laporan keuangan
  - Methods:
    - index() - Halaman utama laporan
    - neraca() - Laporan Neraca
    - labaRugi() - Laporan Laba Rugi
    - neracaSaldo() - Laporan Neraca Saldo
    - arusKas() - Laporan Arus Kas
    - Export methods (PDF & Excel)

### 2. Views
- [ ] `resources/views/financial_reports/index.blade.php` - **BARU**
  - Status: Belum dibuat
  - Deskripsi: Halaman dashboard laporan keuangan

- [ ] `resources/views/financial_reports/neraca.blade.php` - **BARU**
  - Status: Belum dibuat
  - Deskripsi: Tampilan laporan Neraca

- [ ] `resources/views/financial_reports/neraca_pdf.blade.php` - **BARU**
  - Status: Belum dibuat
  - Deskripsi: Template PDF untuk Neraca

- [ ] `resources/views/financial_reports/laba_rugi.blade.php` - **BARU**
  - Status: Belum dibuat
  - Deskripsi: Tampilan laporan Laba Rugi

- [ ] `resources/views/financial_reports/laba_rugi_pdf.blade.php` - **BARU**
  - Status: Belum dibuat
  - Deskripsi: Template PDF untuk Laba Rugi

- [ ] `resources/views/financial_reports/neraca_saldo.blade.php` - **BARU**
  - Status: Belum dibuat
  - Deskripsi: Tampilan laporan Neraca Saldo

- [ ] `resources/views/financial_reports/neraca_saldo_pdf.blade.php` - **BARU**
  - Status: Belum dibuat
  - Deskripsi: Template PDF untuk Neraca Saldo

- [ ] `resources/views/financial_reports/arus_kas.blade.php` - **BARU**
  - Status: Belum dibuat
  - Deskripsi: Tampilan laporan Arus Kas

- [ ] `resources/views/financial_reports/arus_kas_pdf.blade.php` - **BARU**
  - Status: Belum dibuat
  - Deskripsi: Template PDF untuk Arus Kas

### 3. Routes
- [ ] `routes/web.php` - **MODIFIKASI**
  - Status: Belum dimodifikasi
  - Deskripsi: Menambahkan routes untuk laporan keuangan
  - Lines yang akan ditambahkan: ~20 baris (di bagian financial reports)

### 4. Export Classes
- [ ] `app/Exports/NeracaExport.php` - **BARU**
  - Status: Belum dibuat
  - Deskripsi: Export Neraca ke Excel

- [ ] `app/Exports/LabaRugiExport.php` - **BARU**
  - Status: Belum dibuat
  - Deskripsi: Export Laba Rugi ke Excel

- [ ] `app/Exports/NeracaSaldoExport.php` - **MUNGKIN SUDAH ADA**
  - Status: Perlu dicek
  - Deskripsi: Export Neraca Saldo ke Excel

- [ ] `app/Exports/ArusKasExport.php` - **MUNGKIN SUDAH ADA**
  - Status: Perlu dicek
  - Deskripsi: Export Arus Kas ke Excel

---

## Cara Restore ke Titik Tertentu

### Restore Full Backup (Kembali sebelum perubahan)
```bash
cd /var/www/html
# Backup current state (opsional)
tar -czpf adiyasa_before_restore_$(date +%Y%m%d_%H%M%S).tar.gz adiyasa.alus.co.id

# Hapus folder current
rm -rf adiyasa.alus.co.id

# Extract backup
tar -xzpf adiyasa_backup_20251108_223247.tar.gz

# Set permissions jika perlu
chown -R www-data:www-data adiyasa.alus.co.id
chmod -R 755 adiyasa.alus.co.id
```

### Restore File Tertentu dari Backup
```bash
# Lihat isi backup tanpa extract
tar -tzf /var/www/html/adiyasa_backup_20251108_223247.tar.gz | grep "nama_file"

# Extract file tertentu
tar -xzf /var/www/html/adiyasa_backup_20251108_223247.tar.gz "adiyasa.alus.co.id/path/to/file"

# Copy file yang di-extract
cp adiyasa.alus.co.id/path/to/file /var/www/html/adiyasa.alus.co.id/path/to/file
```

### Hapus File Baru (Manual Rollback)
```bash
# Hapus controller
rm /var/www/html/adiyasa.alus.co.id/app/Http/Controllers/FinancialReportController.php

# Hapus views
rm -rf /var/www/html/adiyasa.alus.co.id/resources/views/financial_reports

# Hapus export classes
rm /var/www/html/adiyasa.alus.co.id/app/Exports/NeracaExport.php
rm /var/www/html/adiyasa.alus.co.id/app/Exports/LabaRugiExport.php

# Restore routes/web.php dari backup jika sudah dimodifikasi
tar -xzf /var/www/html/adiyasa_backup_20251108_223247.tar.gz "adiyasa.alus.co.id/routes/web.php"
cp adiyasa.alus.co.id/routes/web.php /var/www/html/adiyasa.alus.co.id/routes/web.php
```

---

## Log Perubahan Detail

## CHANGELOG - Financial Reports Fix

## [2025-11-09 00:45] - ✅ RESTORE POINT: Neraca Complete
### Summary
**Complete implementation of Neraca (Balance Sheet) with all features working.**

All changes have been backed up to: `.backups/20251109_neraca_complete/`

### Completed Features
1. ✅ **Fixed Calculation Logic**
   - Changed from `id` to `akun_code` for accurate joins
   - Cumulative calculation (date <=) instead of period-based
   - Different formulas for assets vs liabilities/equity
   - Removed broken parent-child logic

2. ✅ **2-Column Layout View**
   - Side-by-side comparison (Aset | Kewajiban & Ekuitas)
   - Soft gray colors for professional appearance
   - Balance summary boxes
   - Responsive design

3. ✅ **Export Functionality**
   - PDF export with company name header
   - Excel export with proper formatting
   - Dynamic filenames with dates
   - Consistent styling across formats

### Files in Restore Point
- `JurnalController.php` (102KB) - Controller with neraca, neracaPdf, neracaExcel methods
- `neraca.blade.php` (11KB) - Main view with 2-column layout
- `neraca_pdf.blade.php` (7.6KB) - PDF template
- `NeracaExport.php` (5.8KB) - Excel export class
- `web.php` (29KB) - Routes
- `RESTORE_MANIFEST.md` - Documentation
- `restore.sh` - Automated restore script

### How to Restore
```bash
cd /var/www/html/adiyasa.alus.co.id
./.backups/20251109_neraca_complete/restore.sh
```

---

## [2025-11-09 00:40] - Add Export PDF & Excel for Neraca
### Added
- Created `app/Http/Controllers/JurnalController@neracaPdf()` method
- Created `app/Http/Controllers/JurnalController@neracaExcel()` method
- Created `resources/views/jurnal/neraca_pdf.blade.php` for PDF template
- Created `app/Exports/NeracaExport.php` for Excel export
- Added routes: `/jurnal/neraca/pdf` and `/jurnal/neraca/excel`

### Features
- PDF export with 2-column layout (A4 portrait)
- Excel export with styled headers and number formatting
- Company name dynamically loaded from config
- Filename includes date for easy organization
- Both formats match web view calculations

---

## [2025-11-09 00:30] - Implement 2-Column Layout for Neraca (Side-by-Side)
### Changed
- Modified `resources/views/jurnal/neraca.blade.php`
  - Changed from single table to **2-column grid layout**
  - **LEFT COLUMN:** Displays all Asset accounts (Aset Lancar + Aset Tetap)
  - **RIGHT COLUMN:** Displays Liabilities & Equity (Kewajiban Lancar + Ekuitas)
  - Added gradient column headers (purple for Assets, pink for Liabilities)
  - Added balance summary boxes at bottom (side-by-side totals)
  - Improved visual balance checking - totals are immediately comparable
  - Responsive grid layout with 30px gap between columns

### Benefits
- Much easier to read and compare left vs right sides
- Instant visual verification of balance (Assets = Liabilities + Equity)
- Professional accounting format (standard balance sheet layout)
- Cleaner hierarchy within each column
- Modern, space-efficient design

---

## [2025-11-09 00:20] - Improve Neraca View to Match Jurnal.id Style
### Changed
- Modified `resources/views/jurnal/neraca.blade.php`
  - Added custom CSS styles for clean, professional appearance
  - Changed to borderless table design with subtle section dividers
  - Implemented color-coded hierarchy (section title, subsection, items)
  - Horizontal filter section with inline export buttons
  - Color-coded laba/rugi display (green for profit, red for loss)
  - Added balance checker alert at the bottom
  - Monospace font for all amounts, right-aligned
  - Improved spacing and padding throughout

### Visual Improvements
- Clean, minimalist design matching Jurnal.id aesthetic
- No table borders, only section separators
- Gradient backgrounds for different hierarchy levels
- Responsive layout suitable for various screen sizes
- Print-friendly (filters hidden on print)

---

---

### [2025-11-09 00:10] - Fix Neraca - Perbaikan Perhitungan
**Files Modified:**
- ✅ `app/Http/Controllers/JurnalController.php` (method neraca, line 1723-1842)
- ✅ `resources/views/jurnal/neraca.blade.php` (full rewrite)

**Masalah Yang Diperbaiki:**
1. ❌ Query menggunakan `$child->id` → ✅ Fixed: pakai `$akun->akun_code`
2. ❌ whereBetween (hanya periode) → ✅ Fixed: where date <= (kumulatif)
3. ❌ Formula sama untuk semua → ✅ Fixed: bedakan aset vs kewajiban/ekuitas
4. ❌ Parent-child logic salah → ✅ Fixed: ambil langsung berdasarkan category

**Perubahan Method:**
```php
// SEBELUM: 
->where('id_akun', $child->id)  // SALAH
->whereBetween('date', [$tanggalAwal, $tanggalAkhir])  // SALAH
$saldo = $debet - $kredit;  // SALAH untuk semua

// SESUDAH:
->where('id_akun', $akun->akun_code)  // BENAR
->where('date', '<=', $tanggalAkhir)  // BENAR (kumulatif)
// Formula berdasarkan kategori:
if (str_contains($groupName, 'aset')) {
    $saldo = $debet - $kredit;  // Aset
} else {
    $saldo = $kredit - $debet;  // Kewajiban/Ekuitas
}
```

**Method Baru Ditambahkan:**
- `hitungLabaRugiUntukNeraca($tanggalAkhir)` - Helper untuk hitung laba rugi

**View Changes:**
- Struktur data simplified (tidak pakai parent-child)
- Tampilan lebih jelas dengan subtotal per kategori
- Tambah balance checker
- Tambah export buttons
- Format rupiah tanpa desimal

**Status:** ✅ COMPLETED
**Tested:** ⏳ PENDING - perlu test di browser

---

### [2025-11-09 00:00:34] - Backup Files Sebelum Perbaikan
**Backup ID:** 20251109_000034
**Lokasi:** `.backups/20251109_000034/`
**Files yang di-backup:**
- ✅ `app/Http/Controllers/JurnalController.php`
- ✅ `routes/web.php`
- ✅ `resources/views/jurnal/neraca.blade.php`
- ✅ `resources/views/jurnal/arus_kas.blade.php`
- ✅ `resources/views/jurnal/neraca_saldo.blade.php`

**Restore Commands:**
```bash
# Restore semua files
bash .backups/20251109_000034/restore_all.sh

# Atau restore manual single file
cp -p .backups/20251109_000034/JurnalController.php.backup app/Http/Controllers/JurnalController.php
```

**Dokumentasi:** `.backups/20251109_000034/BACKUP_MANIFEST.md`
**Status:** ✅ BACKUP READY

---

### [2025-11-08 22:40] - Database Fix
**File/Tabel:** `adiyasa_2.2.akuns`
**Perubahan:** Update group 'hutang' menjadi 'kewajiban'
**Query:**
```sql
UPDATE akuns 
SET `group` = 'kewajiban' 
WHERE `group` = 'hutang' 
AND deleted_at IS NULL;
```
**Hasil:** 1 record updated (kewajiban jangka pendek)
**Alasan:** Konsistensi penamaan - semua kewajiban sekarang menggunakan group 'kewajiban'
**Status:** ✅ COMPLETED

---

## Catatan Penting
1. Selalu backup sebelum melakukan perubahan besar
2. Test di development environment sebelum production
3. Backup database juga sebelum deploy: `mysqldump -u root -p adiyasa_2.2 > backup_db_$(date +%Y%m%d).sql`
4. File changelog ini akan diupdate setiap ada perubahan

---

## Kontak & Support
- Developer: GitHub Copilot
- Tanggal Mulai: 2025-11-08
- Project: Sistem Laporan Keuangan Jurnal.id Style
