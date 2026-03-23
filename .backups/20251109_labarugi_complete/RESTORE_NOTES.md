# Restore Point: Laporan Laba Rugi Complete
**Date:** November 9, 2025
**Status:** ✅ Fully Functional

## What's Included in This Backup:

### 1. Controller
- **JurnalController.php**
  - Method: `labaRugi()` - Main view with 6 category breakdown
  - Method: `labaRugiPdf()` - PDF export with updated structure
  - Method: `labaRugiExcel()` - Excel export with updated structure

### 2. Views
- **laba_rugi.blade.php** - Main view
  - 3-column layout: Kode Akun | Nama Akun | Jumlah
  - 6 category breakdown: Pendapatan, Pendapatan Lainnya, HPP, Beban Usaha, Beban Lainnya, Depresiasi
  - Color-coded profit/loss display
  - Filter section with date range
  - Export buttons (PDF & Excel)
  - Company name header

- **laba_rugi_pdf.blade.php** - PDF template
  - Print-friendly A4 portrait layout
  - 3-column format matching main view
  - All 6 categories with subsections
  - Professional styling for printing

### 3. Export Class
- **LabaRugiExport.php**
  - Excel export with Maatwebsite\Excel
  - 3-column format with styling
  - Section headers, subsections, subtotals
  - Color-coded final result
  - Accounting number format

### 4. Routes
- **web.php**
  - GET /jurnal/labarugi → JurnalController@labaRugi
  - GET /jurnal/labarugi/pdf → JurnalController@labaRugiPdf
  - GET /jurnal/labarugi/excel → JurnalController@labaRugiExcel

## Features Implemented:

✅ **Calculation Structure:**
- Period-based calculation (whereBetween date range)
- 6 separate categories for detailed breakdown
- Formula: Pendapatan (kredit-debet), Beban (debet-kredit)
- Laba Kotor = Total Pendapatan - HPP
- Laba Bersih = Laba Kotor - Total Beban

✅ **Display Features:**
- Account code displayed in separate column (monospace font)
- Account name in middle column
- Amount right-aligned in third column
- Subsection grouping with subtotals
- Green highlight for profit, red for loss
- Responsive design with clean styling

✅ **Export Functionality:**
- PDF download with professional layout
- Excel export with conditional formatting
- Both exports match main view structure
- Date range filter support

## How to Restore:

```bash
cd /var/www/html/adiyasa.alus.co.id

# Restore controller
cp .backups/20251109_labarugi_complete/JurnalController.php app/Http/Controllers/

# Restore views
cp .backups/20251109_labarugi_complete/laba_rugi.blade.php resources/views/jurnal/
cp .backups/20251109_labarugi_complete/laba_rugi_pdf.blade.php resources/views/jurnal/

# Restore export class
cp .backups/20251109_labarugi_complete/LabaRugiExport.php app/Exports/

# Restore routes
cp .backups/20251109_labarugi_complete/web.php routes/

# Clear caches
php artisan route:clear
php artisan route:cache
php artisan view:clear
```

## URLs:
- Main View: https://adiyasa.alus.co.id/jurnal/labarugi
- PDF Export: https://adiyasa.alus.co.id/jurnal/labarugi/pdf
- Excel Export: https://adiyasa.alus.co.id/jurnal/labarugi/excel

## Database Categories Used:
1. `pendapatan` → Pendapatan Usaha
2. `pendapatan lainnya` → Pendapatan Lainnya
3. `harga pokok penjualan` → HPP
4. `beban` → Beban Usaha
5. `beban lainnya` → Beban Lainnya
6. `depresiasi dan amortisasi` → Depresiasi

## Notes:
- All calculations tested and verified
- Export functions fully operational
- View cache cleared after all changes
- Routes cached successfully
- 3-column layout matching reference design
- Company name from config displayed in header

## Previous Restore Point:
- `.backups/20251109_neraca_complete/` - Neraca (Balance Sheet) completion
