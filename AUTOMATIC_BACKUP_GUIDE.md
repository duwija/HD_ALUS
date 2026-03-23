# Panduan Backup Database Otomatis

## Daftar Isi
1. [Overview](#overview)
2. [Setup Manual Backup](#setup-manual-backup)
3. [Setup Automatic Backup dengan Cron](#setup-automatic-backup-dengan-cron)
4. [Monitoring dan Troubleshooting](#monitoring-dan-troubleshooting)

---

## Overview

Sistem ini menyediakan 2 cara untuk backup database tenant:

### 1. **Laravel Artisan Command**
- Command: `php artisan tenant:backup-database`
- Fitur: Backup per tenant atau semua tenant sekaligus
- Auto-cleanup: Hapus backup lama (>7 hari)
- Compression: Opsional gzip compression

### 2. **Bash Script**
- Script: `backup_all_tenants.sh`
- Fitur: Wrapper untuk artisan command dengan logging
- Ideal untuk cron job

---

## Setup Manual Backup

### Backup Semua Tenant
```bash
cd /var/www/kencana.alus.co.id
php artisan tenant:backup-database
```

### Backup Tenant Tertentu (by ID)
```bash
php artisan tenant:backup-database 5
```

### Backup Tenant Tertentu (by Rescode)
```bash
php artisan tenant:backup-database PN
```

### Backup dengan Compression
```bash
php artisan tenant:backup-database --compress
php artisan tenant:backup-database PN --compress
```

### Menggunakan Bash Script
```bash
cd /var/www/kencana.alus.co.id
chmod +x backup_all_tenants.sh
./backup_all_tenants.sh
./backup_all_tenants.sh --compress
```

---

## Setup Automatic Backup dengan Cron

### 1. Edit Crontab
```bash
sudo crontab -e
```

### 2. Tambahkan Cron Job

**Backup Harian (Setiap Hari Jam 02:00)**
```bash
0 2 * * * cd /var/www/kencana.alus.co.id && /usr/bin/php artisan tenant:backup-database >> /var/www/kencana.alus.co.id/storage/logs/backup.log 2>&1
```

**Backup Harian dengan Compression (Jam 02:00)**
```bash
0 2 * * * cd /var/www/kencana.alus.co.id && /usr/bin/php artisan tenant:backup-database --compress >> /var/www/kencana.alus.co.id/storage/logs/backup.log 2>&1
```

**Backup Menggunakan Bash Script (Recommended)**
```bash
0 2 * * * /var/www/kencana.alus.co.id/backup_all_tenants.sh
```

**Backup 2x Sehari (02:00 dan 14:00)**
```bash
0 2,14 * * * /var/www/kencana.alus.co.id/backup_all_tenants.sh
```

**Backup Setiap 6 Jam**
```bash
0 */6 * * * /var/www/kencana.alus.co.id/backup_all_tenants.sh
```

### 3. Cron Time Format
```
┌───────────── minute (0 - 59)
│ ┌───────────── hour (0 - 23)
│ │ ┌───────────── day of month (1 - 31)
│ │ │ ┌───────────── month (1 - 12)
│ │ │ │ ┌───────────── day of week (0 - 6) (Sunday=0)
│ │ │ │ │
* * * * * command
```

**Contoh:**
- `0 2 * * *` - Setiap hari jam 02:00
- `0 */4 * * *` - Setiap 4 jam
- `0 2 * * 0` - Setiap Minggu jam 02:00
- `0 2 1 * *` - Tanggal 1 setiap bulan jam 02:00

### 4. Verify Cron Job
```bash
sudo crontab -l
```

---

## Lokasi Backup File

Setiap tenant memiliki direktori backup sendiri:

```
public/tenants/
├── KC/backup/           # Tenant Kencana
│   ├── kencana_2.2_2026-02-07_020001.sql
│   └── kencana_2.2_2026-02-06_020001.sql.gz
├── PN/backup/           # Tenant Perumnet
│   ├── perumnet_2026-02-07_020001.sql
│   └── perumnet_2026-02-06_020001.sql
└── AD/backup/           # Tenant Adiyasa
    └── adiyasa_2.2_2026-02-07_020001.sql
```

**Format Nama File:**
```
{database_name}_{YYYY-MM-DD}_{HHmmss}.sql
{database_name}_{YYYY-MM-DD}_{HHmmss}.sql.gz  (jika compressed)
```

---

## Monitoring dan Troubleshooting

### 1. Check Log File
```bash
tail -f /var/www/kencana.alus.co.id/storage/logs/backup.log
```

### 2. Check Backup Files
```bash
# List semua backup
ls -lh /var/www/kencana.alus.co.id/public/tenants/*/backup/

# Check backup tenant tertentu
ls -lh /var/www/kencana.alus.co.id/public/tenants/PN/backup/

# Count files per tenant
find /var/www/kencana.alus.co.id/public/tenants/*/backup/ -type f | wc -l
```

### 3. Check Disk Space
```bash
df -h
du -sh /var/www/kencana.alus.co.id/public/tenants/*/backup/
```

### 4. Manual Cleanup
```bash
# Hapus backup lebih dari 7 hari
find /var/www/kencana.alus.co.id/public/tenants/*/backup/ -name "*.sql*" -mtime +7 -delete

# Hapus backup lebih dari 30 hari
find /var/www/kencana.alus.co.id/public/tenants/*/backup/ -name "*.sql*" -mtime +30 -delete
```

### 5. Test Restore Backup
```bash
# Extract compressed backup
gunzip perumnet_2026-02-07_020001.sql.gz

# Restore to database
mysql -u root -p perumnet < perumnet_2026-02-07_020001.sql
```

### 6. Common Issues

**Issue: Permission Denied**
```bash
chmod +x /var/www/kencana.alus.co.id/backup_all_tenants.sh
chown apache:apache /var/www/kencana.alus.co.id/public/tenants/*/backup/ -R
```

**Issue: Backup File Empty**
```bash
# Check mysqldump command
which mysqldump
mysqldump --version

# Check database credentials
mysql -u root -p -e "SHOW DATABASES;"
```

**Issue: Cron Not Running**
```bash
# Check cron service
systemctl status crond

# Restart cron service
sudo systemctl restart crond

# Check cron logs
tail -f /var/log/cron
```

---

## Advanced Configuration

### Email Notification on Failure

Edit `backup_all_tenants.sh` dan uncomment bagian email:
```bash
echo "Database backup failed. Check logs at ${LOG_FILE}" | mail -s "Backup Failed" admin@alus.co.id
```

Pastikan mail sudah terinstall:
```bash
sudo yum install mailx -y
```

### Upload ke Remote FTP Server

Tambahkan di akhir `backup_all_tenants.sh`:
```bash
# Upload ke FTP
FTP_HOST="backup.server.com"
FTP_USER="backupuser"
FTP_PASS="password"

for backup_dir in /var/www/kencana.alus.co.id/public/tenants/*/backup/; do
    cd "$backup_dir"
    latest_backup=$(ls -t *.sql* | head -1)
    
    if [ -n "$latest_backup" ]; then
        ftp -n $FTP_HOST <<END_SCRIPT
        quote USER $FTP_USER
        quote PASS $FTP_PASS
        binary
        put $latest_backup
        quit
END_SCRIPT
    fi
done
```

### Retention Policy Kustom

Edit `app/Console/Commands/BackupTenantDatabase.php` di method `cleanOldBackups`:
```php
// Keep last 30 days instead of 7
$this->cleanOldBackups($backupDir, 30);
```

---

## Best Practices

1. **Schedule Wisely**
   - Backup saat traffic rendah (malam hari)
   - Jangan terlalu sering untuk database besar

2. **Monitor Disk Space**
   - Regular cleanup old backups
   - Gunakan compression untuk database besar

3. **Test Restore Regularly**
   - Test restore backup minimal 1x per bulan
   - Pastikan backup file tidak corrupt

4. **Multiple Backup Locations**
   - Local backup di server
   - Remote backup di FTP/cloud storage
   - Offline backup (external drive)

5. **Security**
   - Protect backup files dengan permission 0644
   - Encrypt sensitive backups
   - Limit access ke backup directory

---

## Quick Reference

```bash
# Manual backup semua tenant
php artisan tenant:backup-database

# Backup dengan compression
php artisan tenant:backup-database --compress

# Backup tenant tertentu
php artisan tenant:backup-database PN

# List backup files
ls -lh public/tenants/PN/backup/

# Check backup log
tail -f storage/logs/backup.log

# Setup cron (daily at 2 AM)
0 2 * * * /var/www/kencana.alus.co.id/backup_all_tenants.sh
```

---

**Kontak Support:**
- Email: support@alus.co.id
- Developer: dev@alus.co.id
