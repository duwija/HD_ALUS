# QUICK RESTORE REFERENCE CARD

## Backup Info
- **Backup ID:** 20251109_000034
- **Date:** 2025-11-09 00:00:34
- **Location:** `/var/www/html/adiyasa.alus.co.id/.backups/20251109_000034/`

## Files Backed Up
✅ JurnalController.php (95KB)  
✅ web.php (29KB)  
✅ neraca.blade.php (2.5KB)  
✅ arus_kas.blade.php (4.3KB)  
✅ neraca_saldo.blade.php (6.5KB)

---

## RESTORE COMMANDS

### 🚀 ONE-CLICK RESTORE (ALL FILES)
```bash
cd /var/www/html/adiyasa.alus.co.id
bash .backups/20251109_000034/restore_all.sh
```

### 🎯 SELECTIVE RESTORE

#### Restore Controller Only
```bash
cd /var/www/html/adiyasa.alus.co.id
cp -p .backups/20251109_000034/JurnalController.php.backup \
     app/Http/Controllers/JurnalController.php
```

#### Restore Routes Only
```bash
cd /var/www/html/adiyasa.alus.co.id
cp -p .backups/20251109_000034/web.php.backup routes/web.php
```

#### Restore Neraca View
```bash
cd /var/www/html/adiyasa.alus.co.id
cp -p .backups/20251109_000034/views/neraca.blade.php.backup \
     resources/views/jurnal/neraca.blade.php
```

#### Restore All Views
```bash
cd /var/www/html/adiyasa.alus.co.id
cp -p .backups/20251109_000034/views/*.backup \
     resources/views/jurnal/
# Rename .backup extension
cd resources/views/jurnal/
for f in *.backup; do mv "$f" "${f%.backup}"; done
```

### 🗑️ Remove New Files
```bash
cd /var/www/html/adiyasa.alus.co.id
rm -f resources/views/jurnal/laba_rugi.blade.php
rm -f resources/views/jurnal/laba_rugi_pdf.blade.php
```

---

## AFTER RESTORE

### Clear Cache
```bash
cd /var/www/html/adiyasa.alus.co.id
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### Restart Services (if needed)
```bash
systemctl restart php-fpm
systemctl restart nginx
# or
systemctl restart httpd
```

---

## VERIFY RESTORE

```bash
# Check file dates
ls -lh app/Http/Controllers/JurnalController.php
ls -lh routes/web.php
ls -lh resources/views/jurnal/neraca.blade.php

# Check backup exists
ls -lh .backups/20251109_000034/
```

---

## EMERGENCY FULL PROJECT RESTORE

If everything breaks, restore from full backup:
```bash
cd /var/www/html
rm -rf adiyasa.alus.co.id
tar -xzpf adiyasa_backup_20251108_223247.tar.gz
```

---

## NOTES
- ✅ All backups preserve permissions (`cp -p`)
- ✅ Original timestamps maintained
- ✅ Safe to restore multiple times
- ⚠️ Always test after restore

**Documentation:** `.backups/20251109_000034/BACKUP_MANIFEST.md`
