# BACKUP DIRECTORY - Financial Reports

## 📦 Available Backups

### ✅ **20251109_financial_reports_complete** (LATEST - RECOMMENDED)
**Date:** 9 November 2025, 03:35 WIB  
**Size:** 40 KB (compressed)  
**Status:** ✅ Production Ready - All Reports Complete

**Includes:**
- ✅ Neraca (Balance Sheet) - Fixed & Complete
- ✅ Laba Rugi (Income Statement) - New, 6 categories
- ✅ Arus Kas (Cash Flow) - Enhanced with modal
- ✅ Buku Besar (General Ledger) - Fixed calculation & parent-child UI

**Quick Restore:**
```bash
cd /var/www/html/adiyasa.alus.co.id/.backups/20251109_financial_reports_complete
./restore.sh
```

---

### 📁 20251109_labarugi_complete
**Date:** 9 November 2025, 02:32 WIB  
**Size:** 33 KB  
**Content:** Laba Rugi only

---

### 📁 20251109_neraca_complete
**Date:** 9 November 2025, 01:44 WIB  
**Content:** Neraca only

---

## 🔄 How to Restore

### Option 1: Interactive Restore (Recommended)
```bash
cd /var/www/html/adiyasa.alus.co.id/.backups/20251109_financial_reports_complete
./restore.sh
```

### Option 2: Manual Restore All Files
```bash
cd /var/www/html/adiyasa.alus.co.id

# Extract if needed
tar -xzf .backups/20251109_financial_reports_complete.tar.gz -C .backups/

# Copy files
cp .backups/20251109_financial_reports_complete/JurnalController.php app/Http/Controllers/
cp .backups/20251109_financial_reports_complete/*.blade.php resources/views/jurnal/
cp .backups/20251109_financial_reports_complete/*Export.php app/Exports/

# Clear cache
php artisan view:clear
php artisan route:cache
php artisan config:clear
```

### Option 3: Restore Specific Report Only
See `RESTORE_NOTES.md` in backup directory for detailed commands.

---

## 📝 Documentation

Each backup contains:
- `RESTORE_NOTES.md` - Complete documentation
- `restore.sh` - Interactive restore script
- All source files

---

## ⚠️ Important Notes

1. Always backup current files before restoring
2. Clear Laravel cache after restore
3. Test all features after restore
4. Check file permissions (644 for files, 755 for directories)

---

**Last Updated:** 9 November 2025, 03:38 WIB
